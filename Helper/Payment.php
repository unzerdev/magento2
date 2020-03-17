<?php

namespace Heidelpay\MGW\Helper;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
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
    public const STATUS_READY_TO_CAPTURE = 'ready to capture';

    /**
     * @var Order\InvoiceRepository
     */
    private $_invoiceRepository;

    /**
     * @var OrderRepository
     */
    private $_orderRepository;

    /**
     * @var Order\StatusResolver
     */
    private $_orderStatusResolver;

    /**
     * Payment constructor.
     * @param Order\InvoiceRepository $invoiceRepository
     * @param OrderRepository $orderRepository
     * @param Order\StatusResolver $orderStatusResolver
     */
    public function __construct(
        Order\InvoiceRepository $invoiceRepository,
        OrderRepository $orderRepository,
        Order\StatusResolver $orderStatusResolver
    )
    {
        $this->_invoiceRepository = $invoiceRepository;
        $this->_orderRepository = $orderRepository;
        $this->_orderStatusResolver = $orderStatusResolver;
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @throws HeidelpayApiException
     */
    public function processState(Order $order, \heidelpayPHP\Resources\Payment $payment)
    {
        /** @var string $state */
        $state = $order->getState();

        switch ($state) {
            case Order::STATE_COMPLETE:
                $this->processStateForCompleteOrder($order, $payment);
                break;
            case Order::STATE_NEW:
                $this->processStateForNewOrder($order, $payment);
                break;
            case Order::STATE_PAYMENT_REVIEW:
                $this->processStateForPaymentReview($order, $payment);
                break;
            case Order::STATE_PROCESSING:
                $this->processStateForProcessingOrder($order, $payment);
                break;
        }
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @throws HeidelpayApiException
     */
    protected function processStateForCompleteOrder(
        Order $order,
        \heidelpayPHP\Resources\Payment $payment
    )
    {
        if ($payment->isCanceled()) {
            $this->cancelOrder($order, $payment);
            $this->setOrderState($order, Order::STATE_COMPLETE);
            return;
        }
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @throws HeidelpayApiException
     */
    protected function processStateForNewOrder(
        Order $order,
        \heidelpayPHP\Resources\Payment $payment
    )
    {
        if ($payment->isCanceled()) {
            $this->cancelOrder($order, $payment);
            return;
        }

        /** @var Authorization|Charge $transaction */
        $transaction = $payment->getAuthorization() ?? $payment->getChargeByIndex(0);

        if ($transaction->isError()) {
            $this->cancelOrder($order, $payment);
            return;
        }

        $state = null;
        $status = null;

        if ($payment->isPending() && $transaction->isSuccess()) {
            if ($this->isInvoicePayment($payment)) {
                $state = Order::STATE_PROCESSING;
                $status = null;
            } elseif ($transaction instanceof Authorization) {
                $state = Order::STATE_PROCESSING;
                $status = self::STATUS_READY_TO_CAPTURE;
            }
        } elseif ($payment->isCompleted()) {
            $state = Order::STATE_PROCESSING;
            $status = null;
        }

        if ($state !== null) {
            $this->setOrderState($order, $state, $status);
        }
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @throws HeidelpayApiException
     */
    protected function processStateForPaymentReview(
        Order $order,
        \heidelpayPHP\Resources\Payment $payment
    )
    {
        $isInvoice = $this->isInvoicePayment($payment);

        if ($isInvoice && $payment->isCanceled()) {
            $this->cancelOrder($order, $payment);
        } elseif ($isInvoice && $payment->isCompleted()) {
            $this->setOrderState($order, Order::STATE_COMPLETE);
        } elseif ($payment->isChargeBack()) {
            $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW, Order::STATUS_FRAUD);
        }
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @throws HeidelpayApiException
     */
    protected function processStateForProcessingOrder(
        Order $order,
        \heidelpayPHP\Resources\Payment $payment
    )
    {
        if ($order->getStatus() === self::STATUS_READY_TO_CAPTURE) {
            if ($payment->isCanceled()) {
                $this->cancelOrder($order, $payment);
            } elseif ($payment->isCompleted() && !$this->isInvoicePayment($payment)) {
                $this->setOrderState($order, Order::STATE_PROCESSING);
            }
        } elseif ($payment->isCanceled()) {
            if ($payment->getAmount()->getCharged() > 0) {
                $this->setOrderState($order, Order::STATE_CLOSED);
            } else {
                $this->cancelOrder($order, $payment);
            }
        } elseif ((($this->isInvoicePayment($payment) && $payment->isPending()) || $payment->isPaymentReview())
            && $this->isOrderShipped($order)) {
            $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW);
        } elseif ($payment->isCompleted() && $this->isOrderShipped($order)) {
            $this->setOrderState($order, Order::STATE_COMPLETE);
        } elseif ($payment->isChargeBack()) {
            $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW, Order::STATUS_FRAUD);
        }
    }

    /**
     * @param \heidelpayPHP\Resources\Payment $payment
     * @return bool
     */
    private function isInvoicePayment(\heidelpayPHP\Resources\Payment $payment): bool
    {
        return $payment->getPaymentType()->isInvoiceType();
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function isOrderShipped(Order $order): bool
    {
        foreach ($order->getItems() as $orderItem) {
            /** @var Order\Item $orderItem */
            if ($orderItem->getQtyToShip() > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Order $order
     * @param string $state
     * @param string|null $status
     */
    private function setOrderState(Order $order, string $state, ?string $status = null)
    {
        if ($status === null) {
            $status = $this->_orderStatusResolver->getOrderStatusByState($order, $state);
        }

        $order->setState($state);
        $order->setStatus($status);
        $this->_orderRepository->save($order);
    }

    /**
     * @param Order $order
     * @param \heidelpayPHP\Resources\Payment $payment
     * @throws HeidelpayApiException
     */
    public function cancelOrder(
        Order $order,
        \heidelpayPHP\Resources\Payment $payment
    )
    {
        if (!$payment->isCanceled()) {
            $payment->cancelAmount();
        }

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
}
