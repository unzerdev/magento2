<?php

namespace Heidelpay\MGW\Model\Observer;

use Exception;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Authorization;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Abstract observer for payment related webhooks
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
abstract class AbstractPaymentWebhookObserver implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Heidelpay\MGW\Helper\Payment
     */
    protected $_paymentHelper;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * ShipmentObserver constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param \Heidelpay\MGW\Helper\Payment $paymentHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        \Heidelpay\MGW\Helper\Payment $paymentHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_paymentHelper = $paymentHelper;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var string $eventType */
        $eventType = $observer->getEvent()->getData('eventType');

        /** @var Authorization|Charge|Payment $payment */
        $resource = $observer->getEvent()->getData('resource');

        /** @var DataObject $result */
        $result = $observer->getEvent()->getData('result');

        /** @var Payment $payment */
        $payment = $resource;

        if (!$payment instanceof Payment) {
            $payment = $payment->getPayment();
        }

        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('increment_id', $payment->getExternalId(), 'eq')
            ->create();

        /** @var OrderInterface[] $orders */
        $orders = $this->_orderRepository->getList($searchCriteria)->getItems();

        if (count($orders) === 0) {
            $result->setData('message', 'Not found');
            $result->setData('status', 404);
            return;
        }

        /** @var Order $order */
        $order = reset($orders);

        try {
            $this->executeWith($order, $resource);
            $this->_orderRepository->save($order);
        } catch (Exception $e) {
            $result->setData('message', 'Internal server error');
            $result->setData('status', 500);

            $order->addCommentToStatusHistory(sprintf(
                'Failed to process %s event: %s',
                $eventType,
                $e->getMessage()
            ));

            return;
        }

        $order->addCommentToStatusHistory(
            sprintf('Processed %s event', $eventType),
            sprintf('heidelpay %s', $eventType)
        );
    }

    /**
     * @param Order $order
     * @param AbstractHeidelpayResource $resource
     */
    abstract public function executeWith(Order $order, AbstractHeidelpayResource $resource): void;
}
