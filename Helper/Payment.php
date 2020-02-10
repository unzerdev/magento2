<?php

namespace Heidelpay\MGW\Helper;

use Heidelpay\MGW\Model\Method\Base;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Framework\Exception\InputException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment as OrderPayment;
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
     * @var OrderPayment\Transaction\Repository
     */
    private $_transactionRepository;

    /**
     * Payment constructor.
     * @param Order\InvoiceRepository $invoiceRepository
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param StateResolver $orderStateResolver
     * @param StatusResolver $orderStatusResolver
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param OrderPayment\Transaction\Repository $transactionRepository
     */
    public function __construct(
        Order\InvoiceRepository $invoiceRepository,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        StateResolver $orderStateResolver,
        StatusResolver $orderStatusResolver,
        OrderPaymentRepositoryInterface $paymentRepository,
        OrderPayment\Transaction\Repository $transactionRepository
    )
    {
        $this->_invoiceRepository = $invoiceRepository;
        $this->_orderManagement = $orderManagement;
        $this->_orderRepository = $orderRepository;
        $this->_orderSender = $orderSender;
        $this->_orderStateResolver = $orderStateResolver;
        $this->_orderStatusResolver = $orderStatusResolver;
        $this->_paymentRepository = $paymentRepository;
        $this->_transactionRepository = $transactionRepository;
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @return void
     * @throws HeidelpayApiException
     * @throws InputException
     */
    public function handlePaymentCompletion(Order $order, \heidelpayPHP\Resources\Payment $payment): void
    {
        $transactionId = $payment->getChargeByIndex(0)->getId();

        /** @var OrderPayment $payment */
        $payment = $order->getPayment();

        // Needed for updating the invoice when registering a notification. Since this is not saved as part of the
        // payment we need to set it manually, otherwise Magento will remove the transaction ID from our invoice which
        // prevents online refunds.
        $payment->setTransactionId($transactionId);

        /** @var Order\Invoice $invoice */
        $invoice = $order->getInvoiceCollection()->getItemByColumnValue('transaction_id', $transactionId);
        if ((int)$invoice->getState() === Order\Invoice::STATE_OPEN) {
            $invoice->pay();
        }

        /** @var OrderPayment\Transaction $paymentTransaction */
        $paymentTransaction = $this->_transactionRepository->getByTransactionId(
            $payment->getTransactionId(),
            $payment->getId(),
            $order->getId()
        );

        $paymentTransaction->setIsClosed(true);

        $this->_invoiceRepository->save($invoice);
        $this->_paymentRepository->save($payment);
        $this->_transactionRepository->save($paymentTransaction);

        $parentTransaction = $paymentTransaction->getParentTransaction();
        if ($parentTransaction !== null &&
            $parentTransaction->getIsClosed() == false) {
            $parentTransaction->setIsClosed(true);
            $this->_transactionRepository->save($parentTransaction);
        }

        // Need to set to processing, otherwise the state resolver will not complete the order, when we are
        // currently in payment review (e.g. with invoice).
        $order->setState(Order::STATE_PROCESSING);

        $this->handleTransactionSuccess($order);
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
     */
    public function handleTransactionSuccess(Order $order)
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
