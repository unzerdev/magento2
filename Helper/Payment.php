<?php

namespace Heidelpay\MGW\Helper;

use Heidelpay\MGW\Model\Method\Base;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\StateResolver;
use Magento\Sales\Model\Order\StatusResolver;

/**
 * Helper for updating payments and their orders from events
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
class Payment
{
    /**
     * @var Order\InvoiceRepository
     */
    protected $_invoiceRepository;

    /**
     * @var OrderManagementInterface
     */
    protected $_orderManagement;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var StateResolver
     */
    protected $_orderStateResolver;

    /**
     * @var StatusResolver
     */
    protected $_orderStatusResolver;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $_paymentRepository;

    /**
     * Payment constructor.
     * @param Order\InvoiceRepository $invoiceRepository
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param StateResolver $orderStateResolver
     * @param StatusResolver $orderStatusResolver
     * @param OrderPaymentRepositoryInterface $paymentRepository
     */
    public function __construct(
        Order\InvoiceRepository $invoiceRepository,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        StateResolver $orderStateResolver,
        StatusResolver $orderStatusResolver,
        OrderPaymentRepositoryInterface $paymentRepository
    )
    {
        $this->_invoiceRepository = $invoiceRepository;
        $this->_orderManagement = $orderManagement;
        $this->_orderRepository = $orderRepository;
        $this->_orderSender = $orderSender;
        $this->_orderStateResolver = $orderStateResolver;
        $this->_orderStatusResolver = $orderStatusResolver;
        $this->_paymentRepository = $paymentRepository;
    }

    /**
     * @param Order $order
     * @param AbstractHeidelpayResource $resource
     * @throws HeidelpayApiException
     */
    public function handleTransactionError(Order $order, AbstractHeidelpayResource $resource)
    {
        if ($resource instanceof Cancellation) {
            $resource = $resource->getParentResource();
        }

        if (!$resource instanceof Authorization &&
            !$resource instanceof Charge) {
            return;
        }

        if (!$resource->getPayment()->isCanceled()) {
            return;
        }

        if ($resource instanceof Charge) {
            // For charges we need to manually cancel the invoice, since cancelling the order may be a no-op in case
            // we already have invoices for all items.

            $transactionId = $resource
                ->getPayment()
                ->getChargeByIndex(0)
                ->getId();

            /** @var Order\Invoice $invoice */
            $invoice = $order->getInvoiceCollection()->getItemByColumnValue('transaction_id', $transactionId);
            $invoice->cancel();

            $this->_invoiceRepository->save($invoice);
            $this->_orderRepository->save($order);
        }

        $this->_orderManagement->cancel($order->getId());
    }

    /**
     * @param Order $order
     */
    public function handleTransactionPending(Order $order)
    {
        /** @var Base $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();

        /** @var string $state */
        $state = $paymentMethod->getTransactionPendingState();

        // If we have already shipped use the correct state for after shipment if set.
        if ($order->getShipmentsCollection()->count() > 0) {
            $state = $paymentMethod->getAfterShipmentOrderState() ?? $state;
        }

        $order->setState($state);
        $order->setStatus($this->_orderStatusResolver->getOrderStatusByState($order, $order->getState()));

        $this->_orderRepository->save($order);
    }

    /**
     * @param Order $order
     * @param Authorization|Charge|AbstractHeidelpayResource $resource
     */
    public function handleTransactionSuccess(Order $order, AbstractHeidelpayResource $resource)
    {
        $orderState = $this->_orderStateResolver->getStateForOrder($order, [
            $this->_orderStateResolver::IN_PROGRESS,
        ]);

        $order->setState($orderState);
        $order->setStatus($this->_orderStatusResolver->getOrderStatusByState($order, $order->getState()));

        $this->_orderRepository->save($order);

        if (!$order->getEmailSent()) {
            $this->_orderSender->send($order);
        }
    }
}
