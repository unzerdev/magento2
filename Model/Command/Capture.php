<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Helper\Order as OrderHelper;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Vault\VaultDetailsHandlerManager;
use UnzerSDK\Constants\RecurrenceTypes;
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
 */
class Capture extends AbstractCommand
{
    /**
     * @var BuilderInterface
     */
    private BuilderInterface $_transactionBuilder;

    /**
     * @var ChargeFactory
     */
    private ChargeFactory $chargeFactory;

    /**
     * @var VaultDetailsHandlerManager
     */
    private VaultDetailsHandlerManager $vaultDetailsHandlerManager;

    /**
     * @inheritDoc
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        OrderHelper $orderHelper,
        UrlInterface $urlBuilder,
        BuilderInterface $transactionBuilder,
        StoreManagerInterface $storeManager,
        ChargeFactory $chargeFactory,
        VaultDetailsHandlerManager $vaultDetailsHandlerManager
    ) {
        parent::__construct(
            $config,
            $logger,
            $orderHelper,
            $urlBuilder,
            $storeManager
        );

        $this->_transactionBuilder = $transactionBuilder;
        $this->chargeFactory = $chargeFactory;
        $this->vaultDetailsHandlerManager = $vaultDetailsHandlerManager;
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

        $storeCode = $this->getStoreCode((int)$order->getStoreId());

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

        $methodInstance = $payment->getMethodInstance();
        if ($this->isVaultSaveAllowed($methodInstance)) {
            $paymentMethodCode = $methodInstance->getCode();
            $this->vaultDetailsHandlerManager->getHandlerByCode($paymentMethodCode)
                ->handle($commandSubject['payment'], $charge);
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
     * @throws UnzerApiException|LocalizedException
     */
    protected function _chargeExisting(Order $order, string $paymentId, float $amount, string $storeId = null): Charge
    {
        $payment = $this->_getClient($storeId, $order->getPayment()->getMethodInstance())
            ->fetchPayment($paymentId);

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
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    protected function _chargeNew(
        Order $order,
        InfoInterface $payment,
        float $amount
    ): Charge {
        $storeId = (string)$order->getStoreId();

        $currency = $order->getBaseCurrencyCode();
        if ($this->_config->getTransmitCurrency($order->getStore()->getCode()) === $this->_config::CURRENCY_CUSTOMER) {
            $currency = $order->getOrderCurrencyCode();
            $amount = (float)$order->getTotalDue();
        }

        $charge = $this->chargeFactory->create([
            'amount' => $amount,
            'currency' => $currency,
            'returnUrl' => $this->_getCallbackUrl()
        ]);
        $charge->setOrderId($order->getIncrementId());

        $unzerClient = $this->_getClient(
            $storeId,
            $order->getPayment()->getMethodInstance()
        );

        $isSaveToVaultActive = $payment->getAdditionalInformation(
            VaultConfigProvider::IS_ACTIVE_CODE
        );

        /** @var PaymentTokenInterface $vaultPaymentToken */
        $vaultPaymentToken = $payment->getExtensionAttributes()->getVaultPaymentToken();

        if ($isSaveToVaultActive || $vaultPaymentToken) {
            $charge->setRecurrenceType(RecurrenceTypes::ONE_CLICK);
        }

        if ($vaultPaymentToken) {
            $paymentTypeId = $unzerClient->fetchPaymentType($vaultPaymentToken->getGatewayToken());
        } else {
            $paymentTypeId = $this->_getResourceId($payment, $order);
        }

        return $unzerClient->performCharge(
            $charge,
            $paymentTypeId,
            $this->_getCustomerId($payment, $order),
            $this->_orderHelper->createMetadataForOrder($order),
            $this->_orderHelper->createBasketForOrder($order),
        );
    }

    /**
     * @inheritDoc
     *
     * @throws UnzerApiException
     */
    protected function _setPaymentTransaction(
        OrderPayment $payment,
        AbstractUnzerResource $resource
    ): void {
        parent::_setPaymentTransaction($payment, $resource);

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
            $parentTransaction = $this->_transactionBuilder->build(TransactionInterface::TYPE_AUTH);
            $parentTransaction->setIsClosed(false);

            // Make sure we reset the builder since it may be reused and could override data in our transaction.
            $this->_transactionBuilder->reset();
        }

        $payment->setParentTransactionId($parentTransactionId);
        $payment->setShouldCloseParentTransaction(false);
    }
}
