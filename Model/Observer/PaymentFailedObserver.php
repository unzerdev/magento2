<?php

namespace Heidelpay\Gateway2\Model\Observer;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Authorization;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Observer for payment.failed webhook events
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
class PaymentFailedObserver implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * ShipmentObserver constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Authorization|Charge|Payment $payment */
        $payment = $observer->getEvent()->getData('resource');

        if (!$payment instanceof Payment) {
            $payment = $payment->getPayment();
        }

        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('increment_id', $payment->getExternalId(), 'eq')
            ->create();

        /** @var OrderInterface[] $orders */
        $orders = $this->_orderRepository->getList($searchCriteria)->getItems();

        if (count($orders) > 0) {
            $order = $orders[0];
            $order->setState(Order::STATE_PAYMENT_REVIEW);

            $this->_orderRepository->save($order);
        }
    }
}
