<?php

namespace Heidelpay\MGW\Model\Observer;

use heidelpayPHP\Resources\Payment;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Observer for payment.review webhook events
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
class PaymentReviewObserver implements ObserverInterface
{
    /**
     * @var OrderManagementInterface
     */
    protected $_orderManagement;

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
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->_orderManagement = $orderManagement;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Payment $payment */
        $payment = $observer->getEvent()->getData('resource');

        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('increment_id', $payment->getExternalId(), 'eq')
            ->create();

        /** @var OrderInterface[] $orders */
        $orders = $this->_orderRepository->getList($searchCriteria)->getItems();

        if (count($orders) > 0) {
            $this->_orderManagement->cancel($orders[0]->getId());
        }
    }
}
