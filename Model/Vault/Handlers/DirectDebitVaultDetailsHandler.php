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
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;

/**
 * SEPA Direct Debit Vault Details Handler
 *
 * @link  https://docs.unzer.com/
 */
class DirectDebitVaultDetailsHandler implements VaultDetailsHandlerInterface
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
     * @var VaultTokenPersister
     */
    private VaultTokenPersister $vaultTokenPersister;

    /**
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param Json $serializer
     * @param DateTimeFactory $dateTimeFactory
     * @param PaymentTokenResourceModel $paymentTokenResourceModel
     * @param VaultTokenPersister $vaultTokenPersister
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Json $serializer,
        DateTimeFactory $dateTimeFactory,
        PaymentTokenResourceModel $paymentTokenResourceModel,
        VaultTokenPersister $vaultTokenPersister
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->serializer = $serializer;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->paymentTokenResourceModel = $paymentTokenResourceModel;
        $this->vaultTokenPersister = $vaultTokenPersister;
    }

    /**
     * Handle tokens
     *
     * @param PaymentDataObject $payment
     * @param AbstractTransactionType $transaction
     *
     * @return void
     *
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

        $paymentToken = $this->createVaultPaymentToken($transaction, $payment);
        if ($paymentToken !== null) {
            $orderPayment = $payment->getPayment();

            $this->vaultTokenPersister->save($paymentToken, $orderPayment);

            $extensionAttributes = $this->getExtensionAttributes($orderPayment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Create vault payment token for SEPA
     *
     * @param AbstractTransactionType $transaction
     * @param PaymentDataObject $payment
     *
     * @return PaymentTokenInterface|null
     *
     * @throws Exception
     */
    private function createVaultPaymentToken(
        AbstractTransactionType $transaction,
        PaymentDataObject $payment
    ): ?PaymentTokenInterface {
        $paymentType = $transaction->getPayment()->getPaymentType();

        if (!$paymentType instanceof SepaDirectDebit) {
            return null;
        }

        $token = $paymentType->getId();
        if (empty($token)) {
            return null;
        }
        $iban = preg_replace('/\s+/', '', (string)$paymentType->getIban());
        $holder = (string)$paymentType->getHolder();

        if ($iban === '' || $holder === '') {
            return null;
        }
        $maskedIban = $this->maskIban($iban);

        $tokenData = $this->paymentTokenResourceModel->getByOrderPaymentId($payment->getPayment()->getId());

        $paymentToken = null;
        if (empty($tokenData)) {
            $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_ACCOUNT);

            $paymentToken->setGatewayToken($token);
            $paymentToken->setExpiresAt($this->getExpirationDate());
            $paymentToken->setTokenDetails($this->convertDetailsToJSON([
                'gatewayToken' => $token,
                'maskedIban' => $maskedIban,
                'accountHolder' => $holder
            ]));
        }

        return $paymentToken;
    }

    /**
     * @param array $details
     *
     * @return string
     */
    private function convertDetailsToJSON(array $details): string
    {
        $json = $this->serializer->serialize($details);
        return $json ?: '{}';
    }

    /**
     * @param OrderPaymentInterface $payment
     *
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
     * @return string
     */
    private function getExpirationDate(): string
    {
        $expDate = $this->dateTimeFactory->create('now', new DateTimeZone('UTC'));
        $expDate->add(new DateInterval('P1Y'));
        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * @param string $iban
     *
     * @return string
     */
    private function maskIban(string $iban): string
    {
        $len = strlen($iban);
        if ($len <= 6) {
            return $iban;
        }
        $head = substr($iban, 0, 2);
        $tail = substr($iban, -4);
        return $head . str_repeat('*', max(0, $len - 6)) . $tail;
    }
}
