<?php

namespace Heidelpay\Gateway2\Model\Observer;

use Heidelpay\Gateway2\Helper\Order as OrderHelper;
use Heidelpay\Gateway2\Model\Config;
use Heidelpay\Gateway2\Model\PaymentInformation;
use Heidelpay\Gateway2\Model\PaymentInformationFactory;
use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;

class ShipmentObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * @var OrderHelper
     */
    protected $_orderHelper;

    /**
     * @var PaymentInformationFactory
     */
    protected $_paymentInformationFactory;

    /**
     * ShipmentObserver constructor.
     * @param Config $moduleConfig
     * @param OrderHelper $orderHelper
     * @param PaymentInformationFactory $paymentInformationFactory
     */
    public function __construct(
        Config $moduleConfig,
        OrderHelper $orderHelper,
        PaymentInformationFactory $paymentInformationFactory
    ) {
        $this->_moduleConfig = $moduleConfig;
        $this->_orderHelper = $orderHelper;
        $this->_paymentInformationFactory = $paymentInformationFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws HeidelpayApiException
     */
    public function execute(Observer $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if (!$shipment->isObjectNew()) {
            return;
        }

        /** @var Order $order */
        $order = $shipment->getOrder();

        /** @var Order\Invoice $invoice */
        $invoice = $order
            ->getInvoiceCollection()
            ->getFirstItem();

        /** @var PaymentInformation $paymentInformation */
        $paymentInformation = $this->_paymentInformationFactory->create();
        $paymentInformation->load($this->_orderHelper->getExternalId($order), 'external_id');

        if ($paymentInformation->getId() !== null) {
            $client = $this->_moduleConfig->getHeidelpayClient();

            try {
                /** @var Payment $payment */
                $payment = $client->fetchPayment($paymentInformation->getPaymentId());
                $payment->ship($invoice->getId());
            } catch (HeidelpayApiException $e) {
                if ($e->getCode() !== ApiResponseCodes::API_ERROR_TRANSACTION_SHIP_NOT_ALLOWED) {
                    throw $e;
                }
            }
        }
    }
}
