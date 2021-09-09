<?php

namespace Unzer\PAPI\Helper;

use Exception;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;

/**
 * Helper for cancellation state management
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
class Payment
{
    public const STATUS_READY_TO_CAPTURE = 'unzer_ready_to_capture';

    /**
     * @var Order\InvoiceRepository
     */
    private $_invoiceRepository;

    /**
     * @var InvoiceSender
     */
    private $_invoiceSender;

    /**
     * @var LockManagerInterface
     */
    private $_lockManager;

    /**
     * @var OrderRepository
     */
    private $_orderRepository;

    /**
     * @var OrderSender
     */
    private $_orderSender;

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
     * @param InvoiceSender $invoiceSender
     * @param LockManagerInterface $lockManager
     * @param OrderRepository $orderRepository
     * @param OrderSender $orderSender
     * @param Order\OrderStateResolverInterface $orderStateResolver
     * @param Order\StatusResolver $orderStatusResolver
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        InvoiceSender $invoiceSender,
        LockManagerInterface $lockManager,
        OrderRepository $orderRepository,
        OrderSender $orderSender,
        Order\OrderStateResolverInterface $orderStateResolver,
        Order\StatusResolver $orderStatusResolver,
        OrderPaymentRepositoryInterface $paymentRepository,
        TransactionRepositoryInterface $transactionRepository
    )
    {
        $this->_invoiceRepository = $invoiceRepository;
        $this->_invoiceSender = $invoiceSender;
        $this->_lockManager = $lockManager;
        $this->_orderRepository = $orderRepository;
        $this->_orderSender = $orderSender;
        $this->_orderStateResolver = $orderStateResolver;
        $this->_orderStatusResolver = $orderStatusResolver;
        $this->_paymentRepository = $paymentRepository;
        $this->_transactionRepository = $transactionRepository;
    }

    /**
     * @param Order $order
     * @param \UnzerSDK\Resources\Payment $payment
     * @throws UnzerApiException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processState(Order $order, \UnzerSDK\Resources\Payment $payment)
    {
        $lockName = sprintf('unzer_order_%d', $order->getId());

        $this->_lockManager->lock($lockName);

        // Reload order to get current state
        $order = $this->_orderRepository->get($order->getId());

        try {
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
                case PaymentState::STATE_PARTLY:
                    $this->processPartlyState($order, $payment);
                    break;
                case PaymentState::STATE_PAYMENT_REVIEW:
                    $this->processPaymentReviewState($order);
                    break;
                case PaymentState::STATE_PENDING:
                    $this->processPendingState($order, $payment);
                    break;
            }
        } finally {
            $this->_lockManager->unlock($lockName);
        }
    }

    /**
     * @param Order $order
     */
    private function processCanceledState(Order $order)
    {
        // Orders in payment_review can't be cancelled so we must manually
        // change the status so that we can cancel the Order.
        if ($order->isPaymentReview()) {
            $order->setState(Order::STATE_PROCESSING);
        }

        /** @var Order\Invoice[] $invoices */
        $invoices = $order->getInvoiceCollection()->getItems();

        foreach ($invoices as $invoice) {
            $invoice->cancel();
            $this->_invoiceRepository->save($invoice);
        }

        if ($order->canCancel()) {
            $order->cancel();
            $this->_orderRepository->save($order);
        }
    }

    /**
     * @param Order $order
     * @param \UnzerSDK\Resources\Payment $payment
     * @throws UnzerApiException
     */
    private function processCompletedState(Order $order, \UnzerSDK\Resources\Payment $payment)
    {
        $orderPayment = $order->getPayment();

        $transactionId = $payment->getChargeByIndex(0)->getId();

        /** @var Order\Invoice $invoice */
        $invoice = $order->getInvoiceCollection()->getItemByColumnValue('transaction_id', $transactionId);

        if ((int)$invoice->getState() === Order\Invoice::STATE_OPEN) {
            $invoice->pay();

            $order = $invoice->getOrder();
            $orderPayment = $order->getPayment();

            $this->_invoiceRepository->save($invoice);
            $this->_orderRepository->save($order);
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
        if ($order->getState() !== Order::STATE_CANCELED &&
            $order->getState() !== Order::STATE_CLOSED) {
            $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW, Order::STATUS_FRAUD);
        }
    }

    /**
     * @param Order $order
     * @param \UnzerSDK\Resources\Payment $payment
     * @throws UnzerApiException
     */
    private function processPartlyState(Order $order, \UnzerSDK\Resources\Payment $payment)
    {
        $this->processPendingState($order, $payment);
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
     * @param \UnzerSDK\Resources\Payment $payment
     * @throws UnzerApiException
     * @throws Exception
     */
    private function processPendingState(Order $order, \UnzerSDK\Resources\Payment $payment)
    {
        $authorization = $payment->getAuthorization();

        if ($authorization !== null && $authorization->isSuccess() && $order->getState() !== Order::STATE_PROCESSING) {
            $this->setOrderState($order, Order::STATE_PROCESSING, self::STATUS_READY_TO_CAPTURE);
        } elseif ($payment->getPaymentType()->isInvoiceType()) {
            $this->setInvoiceTypeState($order);
        }
    }

    /**
     * @param Order $order
     */
    private function setInvoiceTypeState(Order $order)
    {
        // canShip returns false when the order is currently in payment_review state so we must temporarily change
        // change the state for canShip to return the desired value.
        $order->setState(Order::STATE_PROCESSING);

        // The order has not been shipped yet.
        if ($order->canShip()) {
            $this->setOrderState($order, Order::STATE_PROCESSING);
        } else {
            $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW);
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

        $order->setState($state);
        $order->setStatus($status);

        if ($order->hasDataChanges()) {
            $this->_orderRepository->save($order);
        }

        // Trigger new order email once, if not already sent, since we skipped sending it when creating the order.
        if (!$order->getEmailSent() && !in_array($state, [Order::STATE_NEW, Order::STATE_CANCELED])) {
            $this->_orderSender->send($order);

            foreach ($order->getInvoiceCollection() as $invoice) {
                /** @var Order\Invoice $invoice */
                if (!$invoice->getEmailSent()) {
                    $this->_invoiceSender->send($invoice);
                }
            }
        }
    }
}
