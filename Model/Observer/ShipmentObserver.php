<?php

namespace Heidelpay\Gateway2\Model\Observer;

use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Payment;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection;

class ShipmentObserver implements ObserverInterface
{
    private $paymentMethods = [
        Config::METHOD_CREDITCARD,
    ];

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
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
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

        /** @var Collection $payments */
        $payments = $order->getPaymentsCollection();
        $payments->addAttributeToFilter('method', ['in' => $this->paymentMethods]);

        if ($payments->count() > 0) {
            $client = $this->_moduleConfig->getHeidelpayClient();

            /** @var Payment $payment */
            $payment = $client->fetchPaymentByOrderId($order->getIncrementId());
            $payment->ship();
        }
    }
}