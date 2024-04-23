<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Command;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
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
use UnzerSDK\Unzer;

/**
 * Authorize Command for payments
 *
 * @link  https://docs.unzer.com/
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
     * @var PaymentTokenInterface|null
     */
    private ?PaymentTokenInterface $vaultPaymentToken;

    /**
     * @var Unzer
     */
    private Unzer $unzerClient;

    /**
     * Constructor
     *
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
        Config $config,
        LoggerInterface $logger,
        Order $orderHelper,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        AuthorizationFactory $authorizationFactory,
        CreateRiskDataInterfaceFactory $createRiskDataFactory,
        VaultDetailsHandlerManager $vaultDetailsHandlerManager
    ) {
        parent::__construct(
            $config,
            $logger,
            $orderHelper,
            $urlBuilder,
            $storeManager
        );
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

        $amount = (float)$commandSubject['amount'];

        $order = $payment->getOrder();

        $this->unzerClient = $this->_getClient(
            $order->getStore()->getCode(),
            $payment->getMethodInstance()
        );

        $this->vaultPaymentToken = $payment->getExtensionAttributes()->getVaultPaymentToken();

        try {
            $authorization = $this->createAuthorization($order, $amount);

            $isSaveToVaultActive = $payment->getAdditionalInformation(
                VaultConfigProvider::IS_ACTIVE_CODE
            );

            if ($isSaveToVaultActive || $this->vaultPaymentToken) {
                $authorization->setRecurrenceType(RecurrenceTypes::ONE_CLICK);
            }

            if ($payment->getMethodInstance() instanceof Base && $payment->getMethodInstance()->hasRiskData()) {
                $authorization->setRiskData(
                    $this->createRiskDataFactory->create(['payment' => $payment])->execute()
                );
            }

            $authorization = $this->performAuthorization($authorization, $payment);

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
            $this->processSaveToVault($commandSubject['payment'], $authorization);
        }

        $this->_setPaymentTransaction($payment, $authorization);
        return null;
    }

    /**
     * Perform Authorization
     *
     * @param Authorization $authorization
     * @param OrderPayment $payment
     * @return Authorization
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    protected function performAuthorization(Authorization $authorization, OrderPayment $payment): Authorization
    {
        if ($this->vaultPaymentToken) {
            $resourceId = $this->unzerClient->fetchPaymentType($this->vaultPaymentToken->getGatewayToken());
        } else {
            /** @var string $resourceId */
            $resourceId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_RESOURCE_ID);
        }

        return $this->unzerClient->performAuthorization(
            $authorization,
            $resourceId,
            $this->_getCustomerId($payment, $payment->getOrder()),
            $this->_orderHelper->createMetadataForOrder($payment->getOrder()),
            $this->_orderHelper->createBasketForOrder($payment->getOrder())
        );
    }

    /**
     * Process Save to Vault
     *
     * @param PaymentDataObjectInterface $paymentDataObject
     * @param Authorization $authorization
     * @return void
     * @throws LocalizedException
     * @throws UnzerApiException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    protected function processSaveToVault(
        PaymentDataObjectInterface $paymentDataObject,
        Authorization $authorization
    ): void {
        $paymentMethodCode = $paymentDataObject->getPayment()->getMethodInstance()->getCode();
        $this->vaultDetailsHandlerManager->getHandlerByCode($paymentMethodCode)
            ->handle($paymentDataObject, $authorization);
    }

    /**
     * Create Authorization
     *
     * @param OrderInterface $order
     * @param float $amount
     * @return Authorization
     */
    protected function createAuthorization(OrderInterface $order, float $amount): Authorization
    {
        $authorization = $this->authorizationFactory->create([
            'amount' => $amount,
            'currency' => $order->getBaseCurrencyCode(),
            'returnUrl' => $this->_getCallbackUrl()
        ]);
        $authorization->setOrderId($order->getIncrementId());

        return $authorization;
    }
}
