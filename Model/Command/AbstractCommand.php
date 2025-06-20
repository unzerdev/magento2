<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Helper\Order;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use Unzer\PAPI\Model\Method\Observer\BaseDataAssignObserver;
use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Services\ResourceNameService;
use UnzerSDK\Unzer;
use function get_class;

/**
 * Abstract Command for using the Unzer SDK
 *
 * @link  https://docs.unzer.com/
 */
abstract class AbstractCommand implements CommandInterface
{
    public const KEY_PAYMENT_ID = 'payment_id';

    /**
     * @var Unzer|null
     */
    protected ?Unzer $_client = null;

    /**
     * @var Config
     */
    protected Config $_config;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $_logger;

    /**
     * @var Order
     */
    protected Order $_orderHelper;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $_urlBuilder;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * AbstractCommand constructor.
     *
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Order $orderHelper
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Order $orderHelper,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->_config = $config;
        $this->_logger = $logger;
        $this->_orderHelper = $orderHelper;
        $this->_urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns the URL to which customers are redirected after payment.
     *
     * @return string
     */
    protected function _getCallbackUrl(): string
    {
        return $this->_urlBuilder->getUrl('unzer/payment/callback');
    }

    /**
     * Get Client
     *
     * @param string|null $storeCode
     * @param MethodInterface|null $paymentMethodInstance
     * @return Unzer
     */
    protected function _getClient(?string $storeCode = null, ?MethodInterface $paymentMethodInstance = null): Unzer
    {
        if ($this->_client === null) {
            $this->_client = $this->_config->getUnzerClient($storeCode, $paymentMethodInstance);
        }

        return $this->_client;
    }

    /**
     * Returns the customer ID for given current payment or quote. Creates or update customer on Api side if needed
     *
     * @param InfoInterface $payment
     * @param SalesOrder $order
     *
     * @return string|null
     * @throws UnzerApiException|LocalizedException
     */
    protected function _getCustomerId(InfoInterface $payment, SalesOrder $order): ?string
    {
        /** @var string|null $customerId */
        $customerId = (string)$payment->getAdditionalInformation(BaseDataAssignObserver::KEY_CUSTOMER_ID);

        $customer = $this->getCustomer(
            $customerId,
            $order->getStore()->getCode(),
            $order->getPayment()->getMethodInstance()
        );

        //customer not found on this account. create a new one...
        if ($customer === null) {
            $customer = $this->_orderHelper->createCustomerFromOrder($order, $order->getCustomerEmail(), true);

            $payment->setAdditionalInformation(BaseDataAssignObserver::KEY_CUSTOMER_ID, $customer->getId());
        }

        if (!$this->_orderHelper->validateGatewayCustomerAgainstOrder($order, $customer)) {
            $this->_orderHelper->updateGatewayCustomerFromOrder($order, $customer);
        }

        return $customer->getId();
    }

    /**
     * Get Customer
     *
     * @param string $customerId
     * @param string $storeCode
     * @param MethodInterface $paymentMethodInstance
     * @return Customer|null
     * @throws UnzerApiException
     */
    protected function getCustomer(
        string $customerId,
        string $storeCode,
        MethodInterface $paymentMethodInstance
    ): ?Customer {

        if ($customerId === '') {
            return null;
        }

        try {
            return $this->_getClient($storeCode, $paymentMethodInstance)
                ->fetchCustomer($customerId);
        } catch (UnzerApiException $e) {
            //customer with given customerId not found on this account, create new customer, later.
            if ($e->getCode() === ApiResponseCodes::API_ERROR_CUSTOMER_DOES_NOT_EXIST) {
                return null;
            }
            //all other exceptions are still valid exceptions
            throw $e;
        }
    }

    /**
     * Returns the resource ID for given current payment or quote. Creates or update customer on Api side if needed.
     *
     * @param InfoInterface $payment
     * @param SalesOrder $order
     * @return string|null
     * @throws UnzerApiException|LocalizedException
     */
    protected function _getResourceId(InfoInterface $payment, SalesOrder $order): ?string
    {
        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_RESOURCE_ID);
        if (empty($resourceId)) {
            $papiPayment = $this->_orderHelper->createPaymentFromOrder($order);
            $resourceId = $papiPayment->getId();
        }

        return $resourceId;
    }

    /**
     * Sets the transaction information on the given payment from an authorization or charge.
     *
     * @param OrderPayment $payment
     * @param Authorization|Charge|AbstractUnzerResource $resource
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _setPaymentTransaction(
        OrderPayment $payment,
        AbstractUnzerResource $resource
    ): void {
        $payment->setLastTransId($resource->getId());
        $payment->setTransactionId($resource->getId());
        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending($resource->isPending());

        $payment->setAdditionalInformation(static::KEY_PAYMENT_ID, $resource->getPaymentId());
    }

    /**
     * Writes Unzer Ids of the transaction to order history.
     *
     * @param SalesOrder $order
     * @param AbstractTransactionType $transaction
     */
    protected function addUnzerpayIdsToHistory(SalesOrder $order, AbstractTransactionType $transaction): void
    {
        $order->addCommentToStatusHistory(
            'Unzer ' . ResourceNameService::getClassShortName(get_class($transaction)) . ' transaction: ' .
            'UniqueId: ' . $transaction->getUniqueId() . ' | ShortId: ' . $transaction->getShortId()
        );
    }

    /**
     * Add Unzer error messages to order history.
     *
     * @param SalesOrder $order
     * @param string $code
     * @param string $message
     */
    protected function addUnzerErrorToOrderHistory(SalesOrder $order, string $code, string $message): void
    {
        $order->addCommentToStatusHistory("Unzer Error ($code): $message");
    }

    /**
     * Get Store Code
     *
     * @param int $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode(int $storeId): string
    {
        return $this->storeManager->getStore($storeId)->getCode();
    }

    /**
     * Is Vault Save Allowed
     *
     * @param MethodInterface $methodInstance
     * @return bool
     */
    public function isVaultSaveAllowed(MethodInterface $methodInstance): bool
    {
        return $methodInstance instanceof Base
            && $methodInstance->getVaultCode() !== null
            && $methodInstance->isCreateVaultTokenOnSuccess() === false;
    }
}
