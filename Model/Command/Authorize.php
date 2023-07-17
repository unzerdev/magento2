<?php

namespace Unzer\PAPI\Model\Command;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Api\Data\CreateRiskDataInterfaceFactory;
use Unzer\PAPI\Helper\Order;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use Unzer\PAPI\Model\Method\Observer\BaseDataAssignObserver;
use Unzer\PAPI\Model\Vault\VaultDetailsHandlerManager;
use UnzerSDK\Constants\RecurrenceTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\AuthorizationFactory;

/**
 * Authorize Command for payments
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
 * @package  unzerdev/magento2
 */
class Authorize extends AbstractCommand
{
    /**
     * @var AuthorizationFactory
     */
    private AuthorizationFactory $authorizationFactory;

    /**
     * @var CreateRiskDataInterfaceFactory
     */
    private CreateRiskDataInterfaceFactory $createRiskDataFactory;

    /**
     * @var VaultDetailsHandlerManager
     */
    private VaultDetailsHandlerManager $vaultDetailsHandlerManager;

    /**
     * Constructor
     *
     * @param Session $checkoutSession
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Order $orderHelper
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param AuthorizationFactory $authorizationFactory
     * @param CreateRiskDataInterfaceFactory $createRiskDataFactory
     * @param VaultDetailsHandlerManager $vaultDetailsHandlerManager
     */
    public function __construct(
        Session $checkoutSession,
        Config $config,
        LoggerInterface $logger,
        Order $orderHelper,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        AuthorizationFactory $authorizationFactory,
        CreateRiskDataInterfaceFactory $createRiskDataFactory,
        VaultDetailsHandlerManager $vaultDetailsHandlerManager
    ) {
        parent::__construct($checkoutSession, $config, $logger, $orderHelper, $urlBuilder, $storeManager);
        $this->authorizationFactory = $authorizationFactory;
        $this->createRiskDataFactory = $createRiskDataFactory;
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

        $currency = $order->getBaseCurrencyCode();
        if ($this->_config->getTransmitCurrency($order->getStore()->getCode()) === $this->_config::CURRENCY_CUSTOMER) {
            $currency = $order->getOrderCurrencyCode();
            $amount = (float)$order->getTotalDue();
        }

        try {
            /** @var Authorization $authorization */
            $authorization = $this->authorizationFactory->create([
                'amount' => $amount,
                'currency' => $currency,
                'returnUrl' => $this->_getCallbackUrl()
            ]);
            $authorization->setOrderId($order->getIncrementId());

            $unzerClient = $this->_getClient(
                $order->getStore()->getCode(),
                $payment->getMethodInstance()
            );

            $isSaveToVaultActive = $payment->getAdditionalInformation(
                VaultConfigProvider::IS_ACTIVE_CODE
            );

            $vaultPaymentToken = $payment->getExtensionAttributes()->getVaultPaymentToken();

            if ($isSaveToVaultActive || $vaultPaymentToken) {
                $authorization->setRecurrenceType(RecurrenceTypes::ONE_CLICK);
            }

            if ($vaultPaymentToken) {
                $resourceId = $unzerClient->fetchPaymentType($vaultPaymentToken->getGatewayToken());
            } else {
                /** @var string $resourceId */
                $resourceId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_RESOURCE_ID);
            }

            if ($payment->getMethodInstance() instanceof Base && $payment->getMethodInstance()->hasRiskData()) {
                $authorization->setRiskData(
                    $this->createRiskDataFactory->create(['payment' => $payment])->execute()
                );
            }

            $authorization = $unzerClient->performAuthorization(
                $authorization,
                $resourceId,
                $this->_getCustomerId($payment, $order),
                $this->_orderHelper->createMetadataForOrder($order),
                $this->_orderHelper->createBasketForOrder($order)
            );

            $order->addCommentToStatusHistory('Unzer paymentId: ' . $authorization->getPaymentId());
        } catch (UnzerApiException $e) {
            $this->_logger->error($e->getMerchantMessage(), ['incrementId' => $order->getIncrementId()]);
            throw new LocalizedException(__($e->getClientMessage()));
        }

        $this->addUnzerpayIdsToHistory($order, $authorization);

        if ($authorization->isError()) {
            throw new LocalizedException(__('Failed to authorize payment.'));
        }

        $methodInstance = $payment->getMethodInstance();
        if ($this->isVaultSaveAllowed($methodInstance)) {
            $paymentMethodCode = $payment->getMethodInstance()->getCode();
            $this->vaultDetailsHandlerManager->getHandlerByCode($paymentMethodCode)
                ->handle($commandSubject['payment'], $authorization);
        }

        $this->_setPaymentTransaction($payment, $authorization);
        return null;
    }
}
