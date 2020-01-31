<?php

namespace Heidelpay\MGW\Model\Observer;

use Heidelpay\MGW\Helper\Payment;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Observer for webhooks about completed payments
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
class PaymentCompletedObserver extends AbstractPaymentWebhookObserver
{
    /**
     * @var Order\InvoiceRepository
     */
    protected $_invoiceRepository;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $_paymentRepository;

    /**
     * @var OrderPayment\Transaction\Repository
     */
    protected $_transactionRepository;

    /**
     * PaymentCompletedObserver constructor.
     * @param InvoiceRepository $invoiceRepository
     * @param ObjectManagerInterface $objectManager
     * @param OrderRepositoryInterface $orderRepository
     * @param Payment $paymentHelper
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderPayment\Transaction\Repository $transactionRepository
     */
    public function __construct(
        InvoiceRepository $invoiceRepository,
        ObjectManagerInterface $objectManager,
        OrderRepositoryInterface $orderRepository,
        Payment $paymentHelper,
        OrderPaymentRepositoryInterface $paymentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderPayment\Transaction\Repository $transactionRepository
    )
    {
        parent::__construct($orderRepository, $paymentHelper, $searchCriteriaBuilder);

        $this->_invoiceRepository = $invoiceRepository;
        $this->_objectManager = $objectManager;
        $this->_paymentRepository = $paymentRepository;
        $this->_transactionRepository = $transactionRepository;
    }

    /**
     * @param Order $order
     * @param AbstractHeidelpayResource $resource
     * @return void
     * @throws InputException
     * @throws HeidelpayApiException
     */
    public function executeWith(Order $order, AbstractHeidelpayResource $resource): void
    {
        /** @var \heidelpayPHP\Resources\Payment $resource */

        $transactionId = $resource->getChargeByIndex(0)->getId();

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

        $this->_paymentHelper->handleTransactionSuccess($order, $resource);
    }
}
