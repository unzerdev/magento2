<?php

namespace Unzer\PAPI\Model\Command;

use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Observer\BaseDataAssignObserver;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Capture Command for payments
 *
 * Copyright (C) 2021 Unzer GmbH
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
        StoreManagerInterface $storeManager
    )
    {
        parent::__construct($checkoutSession, $config, $logger, $orderHelper, $urlBuilder, $storeManager);

        $this->_transactionBuilder = $transactionBuilder;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function execute(array $commandSubject)
    {
        /** @var OrderPayment $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var float $amount */
        $amount = $commandSubject['amount'];

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string|null $paymentId */
        $paymentId = $payment->getAdditionalInformation(self::KEY_PAYMENT_ID);

        $storeCode = $this->getStoreCode($order->getStoreId());

        try {
            if ($paymentId !== null) {
                $charge = $this->_chargeExisting($paymentId, $amount, $storeCode);
            } else {
                $charge = $this->_chargeNew($payment, $amount);
                $order->addCommentToStatusHistory('heidelpay paymentId: ' . $charge->getPaymentId());
            }
        } catch (UnzerApiException $e) {
            $this->_logger->error($e->getMerchantMessage(), ['incrementId' => $order->getIncrementId()]);
            throw new LocalizedException(__($e->getClientMessage()));
        }

        $this->addHeidelpayIdsToHistory($order, $charge);

        if ($charge->isError()) {
            throw new LocalizedException(__('Failed to charge payment.'));
        }

        $this->_setPaymentTransaction($payment, $charge);
        return null;
    }

    /**
     * Charges an existing payment.
     *
     * @param string $paymentId
     * @param float $amount
     * @param string|null $storeId
     * @return Charge
     * @throws UnzerApiException
     */
    protected function _chargeExisting(string $paymentId, float $amount, string $storeId = null): Charge
    {
        $payment = $this->_getClient($storeId)->fetchPayment($paymentId);

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
     * @param InfoInterface $payment
     * @param float $amount
     * @return Charge
     * @throws UnzerApiException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _chargeNew(InfoInterface $payment, float $amount): Charge
    {
        /** @var Order $order */
        $order = $payment->getOrder();

        $storeId = $order->getStoreId();

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_RESOURCE_ID);

        return $this->_getClient($storeId)->charge(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getCallbackUrl(),
            $this->_getCustomerId($payment, $order),
            $order->getIncrementId(),
            $this->_orderHelper->createMetadataForOrder($order),
            $this->_orderHelper->createBasketForOrder($order),
            null,
            null,
            null
        );
    }

    /**
     * @inheritDoc
     */
    protected function _setPaymentTransaction(
        OrderPayment $payment,
        AbstractUnzerResource $resource
    ): void
    {
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
