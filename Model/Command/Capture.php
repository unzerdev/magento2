<?php

namespace Unzer\PAPI\Model\Command;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\ChargeFactory;

/**
 * Capture Command for payments
 *
 * Copyright (C) 2021 - today Unzer GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.unzer.com/
 *
 * @author Justin Nuß
 *
 * @package  unzerdev/magento2
 */
class Capture extends AbstractCommand
{
    /**
     * @var BuilderInterface
     */
    private $_transactionBuilder;

    /**
     * @var ChargeFactory
     */
    private $chargeFactory;

    /**
     * @inheritDoc
     * @param TransactionFactory $_transactionFactory
     */
    public function __construct(
        Session $checkoutSession,
        Config $config,
        LoggerInterface $logger,
        \Unzer\PAPI\Helper\Order $orderHelper,
        UrlInterface $urlBuilder,
        BuilderInterface $transactionBuilder,
        StoreManagerInterface $storeManager,
        ChargeFactory $chargeFactory
    ) {
        parent::__construct($checkoutSession, $config, $logger, $orderHelper, $urlBuilder, $storeManager);

        $this->_transactionBuilder = $transactionBuilder;
        $this->chargeFactory = $chargeFactory;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function execute(array $commandSubject): ?ResultInterface
    {
        /** @var OrderPayment $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var float $amount */
        $amount = $commandSubject['amount'];

        $order = $payment->getOrder();

        /** @var string|null $paymentId */
        $paymentId = $payment->getAdditionalInformation(self::KEY_PAYMENT_ID);

        $storeCode = $this->getStoreCode($order->getStoreId());

        try {
            if ($paymentId !== null) {
                $charge = $this->_chargeExisting($order, $paymentId, $amount, $storeCode);
            } else {
                $charge = $this->_chargeNew($order, $payment, $amount);
                $order->addCommentToStatusHistory('Unzer paymentId: ' . $charge->getPaymentId());
            }
        } catch (UnzerApiException $e) {
            $this->_logger->error($e->getMerchantMessage(), ['incrementId' => $order->getIncrementId()]);
            throw new LocalizedException(__($e->getClientMessage()));
        }

        $this->addUnzerpayIdsToHistory($order, $charge);

        if ($charge->isError()) {
            throw new LocalizedException(__('Failed to charge payment.'));
        }

        $this->_setPaymentTransaction($payment, $charge);
        return null;
    }

    /**
     * Charges an existing payment.
     *
     * @param Order $order
     * @param string $paymentId
     * @param float $amount
     * @param string|null $storeId
     * @return Charge
     * @throws UnzerApiException
     */
    protected function _chargeExisting(Order $order, string $paymentId, float $amount, string $storeId = null): Charge
    {
        $payment = $this->_getClient($storeId)->fetchPayment($paymentId);

        if ($this->_config->getTransmitCurrency($order->getStore()->getCode()) === $this->_config::CURRENCY_CUSTOMER) {
            $amount = (float)$order->getTotalDue();
        }

        /** @var Authorization|null $authorization */
        $authorization = $payment->getAuthorization();

        if ($authorization !== null) {
            return $authorization->charge($amount);
        }

        return $payment->charge($amount);
    }

    /**
     * Charges a new payment.
     *
     * @param Order $order
     * @param InfoInterface $payment
     * @param float $amount
     * @return Charge
     * @throws UnzerApiException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _chargeNew(Order $order, InfoInterface $payment, float $amount): Charge
    {
        $storeId = $order->getStoreId();

        $currency = $order->getBaseCurrencyCode();
        if ($this->_config->getTransmitCurrency($order->getStore()->getCode()) === $this->_config::CURRENCY_CUSTOMER) {
            $currency = $order->getOrderCurrencyCode();
            $amount = (float)$order->getTotalDue();
        }

        /** @var Charge $charge */
        $charge = $this->chargeFactory->create([
            'amount' => $amount,
            'currency' => $currency,
            'returnUrl' => $this->_getCallbackUrl()
        ]);
        $charge->setOrderId($order->getIncrementId());

        return $this->_getClient($storeId)->performCharge(
            $charge,
            $this->_getResourceId($payment, $order),
            $this->_getCustomerId($payment, $order),
            $this->_orderHelper->createMetadataForOrder($order),
            $this->_orderHelper->createBasketForOrder($order),
        );
    }

    /**
     * @inheritDoc
     * @throws UnzerApiException
     */
    protected function _setPaymentTransaction(
        OrderPayment $payment,
        AbstractUnzerResource $resource
    ): void {
        parent::_setPaymentTransaction($payment, $resource);

        $parentTransactionId = null;

        if ($resource->getPayment()->getAuthorization()) {
            $parentTransactionId = $resource->getPayment()->getAuthorization()->getId();
        } else {
            $parentTransactionId = $resource->getId() . '-aut';

            $this->_transactionBuilder
                ->setFailSafe(false)
                ->setOrder($payment->getOrder())
                ->setPayment($payment)
                ->setTransactionId($parentTransactionId);

            /** @var Transaction $parentTransaction */
            $parentTransaction = $this->_transactionBuilder->build(Transaction::TYPE_AUTH);
            $parentTransaction->setIsClosed(false);

            // Make sure we reset the builder since it may be reused and could override data in our transaction.
            $this->_transactionBuilder->reset();
        }

        $payment->setParentTransactionId($parentTransactionId);
        $payment->setShouldCloseParentTransaction(false);
    }
}
