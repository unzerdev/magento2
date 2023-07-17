<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Vault\Handlers;

use DateInterval;
use DateTimeZone;
use Exception;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InstantPurchase\Model\QuoteManagement\PaymentConfiguration;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\ResourceModel\PaymentToken as PaymentTokenResourceModel;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;

/**
 * PayPal Vault Details Handler
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
class PaypalVaultDetailsHandler implements VaultDetailsHandlerInterface
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    private PaymentTokenFactoryInterface $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * @var PaymentTokenResourceModel
     */
    private PaymentTokenResourceModel $paymentTokenResourceModel;

    /**
     * Constructor
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param Json $serializer
     * @param PaymentTokenResourceModel $paymentTokenResourceModel
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        DateTimeFactory $dateTimeFactory,
        Json $serializer,
        PaymentTokenResourceModel $paymentTokenResourceModel
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->serializer = $serializer;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->paymentTokenResourceModel = $paymentTokenResourceModel;
    }

    /**
     * Handle tokens
     *
     * @param PaymentDataObject $payment
     * @param AbstractTransactionType $transaction
     * @return void
     * @throws UnzerApiException
     * @throws Exception
     */
    public function handle(PaymentDataObject $payment, AbstractTransactionType $transaction): void
    {
        $isSaveToVaultActive = $payment->getPayment()->getAdditionalInformation(
            VaultConfigProvider::IS_ACTIVE_CODE
        );

        if ($isSaveToVaultActive === false) {
            return;
        }

        $isInstantPurchase = $payment->getPayment()->getAdditionalInformation(
            PaymentConfiguration::MARKER
        );

        if ($isInstantPurchase === true) {
            return;
        }

        // add vault payment token entity to extension attributes
        $paymentToken = $this->createVaultPaymentToken($transaction, $payment);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment->getPayment());
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Get vault payment token entity
     *
     * @param AbstractTransactionType $transaction
     * @param PaymentDataObject $payment
     * @return PaymentTokenInterface|null
     * @throws Exception
     */
    private function createVaultPaymentToken(
        AbstractTransactionType $transaction,
        PaymentDataObject $payment
    ): ?PaymentTokenInterface {
        // Check token existing in gateway response
        $paymentType = $transaction->getPayment()->getPaymentType();
        if (!$paymentType instanceof Paypal) {
            return null;
        }

        $token = $paymentType->getId();
        if (empty($token)) {
            return null;
        }

        // do we have any data to save?
        if (empty($paymentType->getEmail())) {
            return null;
        }

        $tokenData = $this->paymentTokenResourceModel->getByOrderPaymentId($payment->getPayment()->getId());

        $paymentToken = null;
        if (empty($tokenData)) {

            $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_ACCOUNT);

            $paymentToken->setGatewayToken($token);
            $paymentToken->setExpiresAt($this->getExpirationDate());
            $paymentToken->setTokenDetails($this->convertDetailsToJSON([
                'gatewayToken' => $token,
                'payerEmail' => $paymentType->getEmail()
            ]));
        }

        return $paymentToken;
    }

    /**
     * Convert payment token details to JSON
     *
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON(array $details): string
    {
        $json = $this->serializer->serialize($details);
        return $json ?: '{}';
    }

    /**
     * Get payment extension attributes
     *
     * @param OrderPaymentInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(OrderPaymentInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes()
            ?: $this->paymentExtensionFactory->create();

        $payment->setExtensionAttributes($extensionAttributes);
        return $extensionAttributes;
    }

    /**
     * Get expiration date
     *
     * @return string
     * @throws Exception
     */
    private function getExpirationDate(): string
    {
        $expDate = $this->dateTimeFactory->create('now', new DateTimeZone('UTC'));
        $expDate->add(new DateInterval('P1Y'));
        return $expDate->format('Y-m-d 00:00:00');
    }
}
