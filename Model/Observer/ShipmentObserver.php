<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\StatusResolver;
use Magento\Store\Model\StoreManagerInterface;
use Unzer\PAPI\Helper\Payment as PaymentHelper;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Observer for automatically tracking shipments in the Gateway
 *
 * @link  https://docs.unzer.com/
 */
class ShipmentObserver implements ObserverInterface
{
    /**
     * List of payment method codes for which the shipment can be tracked in the gateway.
     */
    public const SHIPPABLE_PAYMENT_METHODS = [
        Config::METHOD_INVOICE_SECURED,
        Config::METHOD_INVOICE_SECURED_B2B,
    ];

    /**
     * @var Config
     */
    protected Config $_moduleConfig;

    /**
     * @var StatusResolver
     */
    protected StatusResolver $_orderStatusResolver;

    /**
     * @var PaymentHelper
     */
    protected PaymentHelper $_paymentHelper;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * ShipmentObserver constructor.
     *
     * @param Config $moduleConfig
     * @param StatusResolver $orderStatusResolver
     * @param PaymentHelper $paymentHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $moduleConfig,
        StatusResolver $orderStatusResolver,
        PaymentHelper $paymentHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->_moduleConfig = $moduleConfig;
        $this->_orderStatusResolver = $orderStatusResolver;
        $this->_paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     * @throws UnzerApiException
     */
    public function execute(Observer $observer): void
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if (!$shipment->isObjectNew()) {
            return;
        }

        $order = $shipment->getOrder();

        $storeCode = $this->getStoreCode((int)$order->getStoreId());

        $methodInstance = $order->getPayment()->getMethodInstance();

        if (!$methodInstance instanceof Base) {
            return;
        }

        $payment = $this->_moduleConfig
            ->getUnzerClient($storeCode, $methodInstance)
            ->fetchPaymentByOrderId($order->getIncrementId());

        $this->_paymentHelper->processState($order, $payment);

        if (in_array($order->getPayment()->getMethod(), self::SHIPPABLE_PAYMENT_METHODS, true)) {
            /** @var Order\Invoice $invoice */
            $invoice = $order
                ->getInvoiceCollection()
                ->getFirstItem();

            try {
                $payment->ship($invoice->getId());
            } catch (UnzerApiException $e) {
                if ($e->getCode() !== ApiResponseCodes::API_ERROR_TRANSACTION_SHIP_NOT_ALLOWED &&
                    $e->getCode() !== ApiResponseCodes::CORE_ERROR_INSURANCE_ALREADY_ACTIVATED) {
                    throw $e;
                }
            }
        }
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
}
