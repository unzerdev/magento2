<?php

namespace Heidelpay\Gateway2\Model\Observer;

use Heidelpay\Gateway2\Model\Config;
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
     * ShipmentObserver constructor.
     * @param Config $moduleConfig
     */
    public function __construct(Config $moduleConfig)
    {
        $this->_moduleConfig = $moduleConfig;
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

        $client = $this->_moduleConfig->getHeidelpayClient();

        try {
            /** @var Payment $payment */
            $payment = $client->fetchPaymentByOrderId($order->getIncrementId());
            $payment->ship($invoice->getId());
        } catch (HeidelpayApiException $e) {
            if ($e->getCode() !== ApiResponseCodes::API_ERROR_TRANSACTION_SHIP_NOT_ALLOWED) {
                throw $e;
            }
        }
    }
}
