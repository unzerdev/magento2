<?php

namespace Heidelpay\MGW\Model\Observer;

use heidelpayPHP\Resources\Payment;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Observer for webhooks about failed and cancelled payments and chargebacks
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
class PaymentFailedObserver extends AbstractPaymentWebhookObserver
{
    /**
     * @var OrderManagementInterface
     */
    protected $_orderManagement;

    /**
     * ShipmentObserver constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderManagementInterface $orderManagement
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderManagementInterface $orderManagement
    ) {
        parent::__construct($orderRepository, $searchCriteriaBuilder);
        $this->_orderManagement = $orderManagement;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param DataObject $result
     * @return void
     */
    public function executeWith(Order $order, Payment $payment, DataObject $result): void
    {
        $this->_orderManagement->cancel($order->getId());
    }
}
