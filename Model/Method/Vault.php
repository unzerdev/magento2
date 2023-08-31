<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

use Exception;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\Method\Vault as MagentoVault;
use Unzer\PAPI\Model\Command\Order;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Unzer Vault
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
class Vault extends MagentoVault
{
    /**
     * @var Order
     */
    private Order $orderCommand;

    /**
     * @var PaymentTokenManagementInterface
     */
    private PaymentTokenManagementInterface $tokenManagement;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory;

    /**
     * @var Json
     */
    private Json $jsonSerializer;

    /**
     * @inheritDoc
     *
     * We need this, because of the decision to use "payment_action = order", which is not supported by magento vault
     * @throws LocalizedException
     * @throws UnzerApiException
     * @throws Exception
     */
    public function order(InfoInterface $payment, $amount): void
    {
        if (!$payment instanceof OrderPaymentInterface) {
            throw new \DomainException('Not implemented');
        }

        $this->attachTokenExtensionAttribute($payment);
        $this->attachCreditCardInfo($payment);

        $this->orderCommand->execute([
            'payment' => $payment,
            'amount' => $amount
        ]);
    }

    /**
     * @var MethodInterface
     */
    private MethodInterface $vaultProvider;

    /**
     * Constructor
     *
     * @param ConfigInterface $config
     * @param ConfigFactoryInterface $configFactory
     * @param ObjectManagerInterface $objectManager
     * @param MethodInterface $vaultProvider
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param Command\CommandManagerPoolInterface $commandManagerPool
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param string $code
     * @param Order $orderCommand
     * @param Json|null $jsonSerializer
     */
    public function __construct(
        ConfigInterface $config,
        ConfigFactoryInterface $configFactory,
        ObjectManagerInterface $objectManager,
        MethodInterface $vaultProvider,
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        Command\CommandManagerPoolInterface $commandManagerPool,
        PaymentTokenManagementInterface $tokenManagement,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        $code,
        Order $orderCommand,
        Json $jsonSerializer
    ) {
        parent::__construct(
            $config,
            $configFactory,
            $objectManager,
            $vaultProvider,
            $eventManager,
            $valueHandlerPool,
            $commandManagerPool,
            $tokenManagement,
            $paymentExtensionFactory,
            $code,
            $jsonSerializer
        );
        $this->orderCommand = $orderCommand;
        $this->tokenManagement = $tokenManagement;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->vaultProvider = $vaultProvider;
    }

    /**
     * Attaches token extension attribute.
     *
     * @param OrderPaymentInterface $orderPayment
     * @return void
     */
    private function attachTokenExtensionAttribute(OrderPaymentInterface $orderPayment): void
    {
        $additionalInformation = $orderPayment->getAdditionalInformation();
        if (empty($additionalInformation[PaymentTokenInterface::PUBLIC_HASH])) {
            throw new \LogicException('Public hash should be defined');
        }

        $customerId = $additionalInformation[PaymentTokenInterface::CUSTOMER_ID] ?? null;

        $publicHash = $additionalInformation[PaymentTokenInterface::PUBLIC_HASH];

        $paymentToken = $this->tokenManagement->getByPublicHash($publicHash, $customerId);

        if ($paymentToken === null) {
            throw new \LogicException("No token found");
        }

        $extensionAttributes = $this->getPaymentExtensionAttributes($orderPayment);
        $extensionAttributes->setVaultPaymentToken($paymentToken);
    }

    /**
     * Returns Payment's extension attributes.
     *
     * @param OrderPaymentInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getPaymentExtensionAttributes(OrderPaymentInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * Attaches credit card info.
     *
     * @param OrderPaymentInterface $payment
     * @return void
     */
    private function attachCreditCardInfo(OrderPaymentInterface $payment): void
    {
        $paymentToken = $payment->getExtensionAttributes()
            ->getVaultPaymentToken();
        if ($paymentToken === null) {
            return;
        }

        $tokenDetails = $paymentToken->getTokenDetails();
        if ($tokenDetails === null) {
            return;
        }

        if (is_string($tokenDetails)) {
            $tokenDetails = $this->jsonSerializer->unserialize($paymentToken->getTokenDetails());
        }
        if (is_array($tokenDetails)) {
            $payment->addData($tokenDetails);
        }
    }

    /**
     * Get Vault Provider
     *
     * @return MethodInterface
     */
    public function getVaultProvider(): MethodInterface
    {
        return $this->vaultProvider;
    }
}
