<?php

namespace Heidelpay\MGW\Model\Observer;

use Heidelpay\MGW\Model\Config;
use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;

/**
 * Observer for automatically tracking shipments in the Gateway
 *
 * Copyright (C) 2019 heidelpay GmbH
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
 * @link  https://docs.heidelpay.com/
 *
 * @author Justin Nuß
 *
 * @package  heidelpay/magento2-merchant-gateway
 */
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer): void
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
