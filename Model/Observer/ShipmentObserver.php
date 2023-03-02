<?php

namespace Unzer\PAPI\Model\Observer;

use Unzer\PAPI\Helper\Payment as PaymentHelper;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use UnzerSDK\Constants\ApiResponseCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\StatusResolver;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Observer for automatically tracking shipments in the Gateway
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
 *
 * @author Justin Nuß
 *
 * @package  unzerdev/magento2
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
    protected $_moduleConfig;

    /**
     * @var StatusResolver
     */
    protected $_orderStatusResolver;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * ShipmentObserver constructor.
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
     * @param Observer $observer
     * @return void
     * @throws UnzerApiException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer): void
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if (!$shipment->isObjectNew()) {
            return;
        }

        $order = $shipment->getOrder();

        $storeCode = $this->getStoreCode($order->getStoreId());

        /** @var MethodInterface $methodInstance */
        $methodInstance = $order->getPayment()->getMethodInstance();

        if (!$methodInstance instanceof Base) {
            return;
        }

        $payment = $this->_moduleConfig
            ->getUnzerClient($storeCode, $methodInstance)
            ->fetchPaymentByOrderId($order->getIncrementId());

        $this->_paymentHelper->processState($order, $payment);

        if (in_array($order->getPayment()->getMethod(), self::SHIPPABLE_PAYMENT_METHODS)) {
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
     * @param int $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode(int $storeId)
    {
        return $this->storeManager->getStore($storeId)->getCode();
    }
}
