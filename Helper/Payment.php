<?php

namespace Heidelpay\MGW\Helper;

use heidelpayPHP\Constants\PaymentState;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

/**
 * Helper for cancellation state management
 *
 * Copyright (C) 2020 heidelpay GmbH
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
    public const STATUS_READY_TO_CAPTURE = 'heidelpay_ready_to_capture';

    /**
     * @var Order\InvoiceRepository
     */
    private $_invoiceRepository;

    /**
     * @var OrderRepository
     */
    private $_orderRepository;

    /**
     * @var Order\OrderStateResolverInterface
     */
    private $_orderStateResolver;

    /**
     * @var Order\StatusResolver
     */
    private $_orderStatusResolver;
    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $_paymentRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    private $_transactionRepository;

    /**
     * Payment constructor.
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderRepository $orderRepository
     * @param Order\OrderStateResolverInterface $orderStateResolver
     * @param Order\StatusResolver $orderStatusResolver
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        OrderRepository $orderRepository,
        Order\OrderStateResolverInterface $orderStateResolver,
        Order\StatusResolver $orderStatusResolver,
        OrderPaymentRepositoryInterface $paymentRepository,
        TransactionRepositoryInterface $transactionRepository
    )
    {
        $this->_invoiceRepository = $invoiceRepository;
        $this->_orderRepository = $orderRepository;
        $this->_orderStateResolver = $orderStateResolver;
        $this->_orderStatusResolver = $orderStatusResolver;
        $this->_paymentRepository = $paymentRepository;
        $this->_transactionRepository = $transactionRepository;
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @throws HeidelpayApiException
     */
    public function processState(Order $order, \heidelpayPHP\Resources\Payment $payment)
    {
        switch ($payment->getState()) {
            case PaymentState::STATE_CANCELED:
                $this->processCanceledState($order);
                break;
            case PaymentState::STATE_COMPLETED:
                $this->processCompletedState($order, $payment);
                break;
            case PaymentState::STATE_CHARGEBACK:
                $this->processChargebackState($order);
                break;
            case PaymentState::STATE_PAYMENT_REVIEW:
                $this->processPaymentReviewState($order);
                break;
            case PaymentState::STATE_PENDING:
                $this->processPendingState($order, $payment);
                break;
        }
    }

    /**
     * @param Order $order
     */
    private function processCanceledState(Order $order)
    {
        if ($order->canCancel()) {
            /** @var Order\Invoice[] $invoices */
            $invoices = $order->getInvoiceCollection()->getItems();

            foreach ($invoices as $invoice) {
                $invoice->cancel();
                $this->_invoiceRepository->save($invoice);
            }

            $order->cancel();

            $this->_orderRepository->save($order);
        }
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @throws HeidelpayApiException
     */
    private function processCompletedState(Order $order, \heidelpayPHP\Resources\Payment $payment)
    {
        $orderPayment = $order->getPayment();

        $transactionId = $payment->getChargeByIndex(0)->getId();

        /** @var Order\Invoice $invoice */
        $invoice = $order->getInvoiceCollection()->getItemByColumnValue('transaction_id', $transactionId);

        if ((int)$invoice->getState() === Order\Invoice::STATE_OPEN) {
            $invoice->pay();

            $this->_invoiceRepository->save($invoice);
            $this->_paymentRepository->save($orderPayment);
        }

        /** @var Order\Payment\Transaction $paymentTransaction */
        $paymentTransaction = $this->_transactionRepository->getByTransactionId(
            $transactionId,
            $orderPayment->getId(),
            $order->getId()
        );

        if (!$paymentTransaction->getIsClosed()) {
            $paymentTransaction->setIsClosed(true);

            $this->_transactionRepository->save($paymentTransaction);

            $parentPaymentTransaction = $paymentTransaction->getParentTransaction();
            if ($parentPaymentTransaction !== null &&
                $parentPaymentTransaction->getIsClosed() == false) {
                $parentPaymentTransaction->setIsClosed(true);
                $this->_transactionRepository->save($parentPaymentTransaction);
            }
        }


        // Need to set to processing, otherwise the state resolver will not complete the order, when we are
        // currently in payment review (e.g. with invoice).
        $order->setState(Order::STATE_PROCESSING);

        $this->setOrderState($order, null, null);
    }

    /**
     * @param Order $order
     */
    private function processChargebackState(Order $order)
    {
        if ($order->getState() === Order::STATE_PAYMENT_REVIEW ||
            $order->getState() === Order::STATE_PROCESSING) {
            $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW, Order::STATUS_FRAUD);
        }
    }

    /**
     * @param Order $order
     */
    private function processPaymentReviewState(Order $order)
    {
        $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW);
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @throws HeidelpayApiException
     */
    private function processPendingState(Order $order, \heidelpayPHP\Resources\Payment $payment)
    {
        $authorization = $payment->getAuthorization();

        if ($authorization !== null && $authorization->isSuccess() && $order->getState() !== Order::STATE_PROCESSING) {
            $this->setOrderState($order, Order::STATE_PROCESSING, self::STATUS_READY_TO_CAPTURE);
        } elseif ($payment->getPaymentType()->isInvoiceType()) {
            $charge = $payment->getChargeByIndex(0);

            if ($charge->isSuccess()) {
                $this->setOrderState($order, Order::STATE_PROCESSING);
            }
        }
    }

    /**
     * @param Order $order
     * @param string $state
     * @param string|null $status
     */
    public function setOrderState(Order $order, ?string $state = null, ?string $status = null)
    {
        if ($state === null) {
            $state = $this->_orderStateResolver->getStateForOrder($order, [
                Order\OrderStateResolverInterface::IN_PROGRESS,
            ]);
        }

        if ($status === null) {
            $status = $this->_orderStatusResolver->getOrderStatusByState($order, $state);
        }

        if ($order->getState() !== $state || $order->getStatus() !== $status) {
            $order->setState($state);
            $order->setStatus($status);
            $this->_orderRepository->save($order);
        }
    }
}
