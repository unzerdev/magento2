<?php
declare(strict_types=1);

namespace Unzer\PAPI\Helper;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Unzer\PAPI\Model\Method\Base;
use Unzer\PAPI\Model\Vault\VaultDetailsHandlerManager;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment as PaymentResource;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;

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
 */
class Payment
{
    public const STATUS_READY_TO_CAPTURE = 'unzer_ready_to_capture';

    /**
     * @var InvoiceRepositoryInterface
     */
    private InvoiceRepositoryInterface $_invoiceRepository;

    /**
     * @var InvoiceSender
     */
    private InvoiceSender $_invoiceSender;

    /**
     * @var LockManagerInterface
     */
    private LockManagerInterface $_lockManager;

    /**
     * @var OrderRepository
     */
    private OrderRepository $_orderRepository;

    /**
     * @var OrderSender
     */
    private OrderSender $_orderSender;

    /**
     * @var Order\OrderStateResolverInterface
     */
    private Order\OrderStateResolverInterface $_orderStateResolver;

    /**
     * @var Order\StatusResolver
     */
    private Order\StatusResolver $_orderStatusResolver;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private OrderPaymentRepositoryInterface $_paymentRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    private TransactionRepositoryInterface $_transactionRepository;

    /**
     * @var VaultDetailsHandlerManager
     */
    private VaultDetailsHandlerManager $vaultDetailsHandlerManager;

    /**
     * @var PaymentDataObjectFactoryInterface
     */
    private PaymentDataObjectFactoryInterface $paymentDataObjectFactory;

    /**
     * Constructor
     *
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param InvoiceSender $invoiceSender
     * @param LockManagerInterface $lockManager
     * @param OrderRepository $orderRepository
     * @param OrderSender $orderSender
     * @param Order\OrderStateResolverInterface $orderStateResolver
     * @param Order\StatusResolver $orderStatusResolver
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param TransactionRepositoryInterface $transactionRepository
     * @param VaultDetailsHandlerManager $vaultDetailsHandlerManager
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
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
        TransactionRepositoryInterface $transactionRepository,
        VaultDetailsHandlerManager $vaultDetailsHandlerManager,
        PaymentDataObjectFactoryInterface $paymentDataObjectFactory
    ) {
        $this->_invoiceRepository = $invoiceRepository;
        $this->_invoiceSender = $invoiceSender;
        $this->_lockManager = $lockManager;
        $this->_orderRepository = $orderRepository;
        $this->_orderSender = $orderSender;
        $this->_orderStateResolver = $orderStateResolver;
        $this->_orderStatusResolver = $orderStatusResolver;
        $this->_paymentRepository = $paymentRepository;
        $this->_transactionRepository = $transactionRepository;
        $this->vaultDetailsHandlerManager = $vaultDetailsHandlerManager;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * Process state
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     * @throws UnzerApiException
     */
    public function processState(OrderInterface $order, PaymentResource $payment): void
    {
        $lockName = sprintf('unzer_order_%d', $order->getId());

        $this->_lockManager->lock($lockName);

        // Reload order to get current state
        $order = $this->_orderRepository->get($order->getId());

        try {
            $this->processVaultDetails($order, $payment);

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
     * Process canceled state
     *
     * @param OrderInterface $order
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function processCanceledState(OrderInterface $order): void
    {
        // Orders in payment_review can't be cancelled so we must manually
        // change the status so that we can cancel the Order.
        if ($order->isPaymentReview()) {
            $order->setState(Order::STATE_PROCESSING);
        }

        // if the payment was voided, we do not want to cancel the whole order and invoice
        if (!$this->isOrderVoided($order)) {

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
    }

    /**
     * Is order voided
     *
     * @param OrderInterface $order
     * @return bool
     * @throws InputException
     */
    private function isOrderVoided(OrderInterface $order): bool
    {
        $voidedPaymentTransaction = $this->_transactionRepository->getByTransactionType(
            TransactionInterface::TYPE_VOID,
            $order->getPayment()->getId()
        );

        return (bool)$voidedPaymentTransaction;
    }

    /**
     * Process complete state
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     */
    private function processCompletedState(OrderInterface $order, PaymentResource $payment): void
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
                !$parentPaymentTransaction->getIsClosed()
            ) {
                $parentPaymentTransaction->setIsClosed(true);
                $this->_transactionRepository->save($parentPaymentTransaction);
            }
        }

        // Need to set to processing, otherwise the state resolver will not complete the order, when we are
        // currently in payment review (e.g. with invoice).
        $order->setState(Order::STATE_PROCESSING);

        $this->setOrderState($order);
    }

    /**
     * Process chargeback state
     *
     * @param OrderInterface $order
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function processChargebackState(OrderInterface $order): void
    {
        if ($order->getState() !== Order::STATE_CANCELED &&
            $order->getState() !== Order::STATE_CLOSED) {
            $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW, Order::STATUS_FRAUD);
        }
    }

    /**
     * Process partly state
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     */
    private function processPartlyState(OrderInterface $order, PaymentResource $payment): void
    {
        $this->processPendingState($order, $payment);
    }

    /**
     * Process payment review state
     *
     * @param OrderInterface $order
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function processPaymentReviewState(OrderInterface $order): void
    {
        $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW);
    }

    /**
     * Process pending state
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     */
    private function processPendingState(OrderInterface $order, PaymentResource $payment): void
    {
        $authorization = $payment->getAuthorization();

        if ($authorization !== null && $authorization->isSuccess() && $order->getState() !== Order::STATE_PROCESSING) {
            $this->setOrderState($order, Order::STATE_PROCESSING, self::STATUS_READY_TO_CAPTURE);
        } elseif ($payment->getPaymentType() instanceof BasePaymentType
            && $payment->getPaymentType()->isInvoiceType()
        ) {
            $this->setInvoiceTypeState($order);
        }
    }

    /**
     * Set Invoice Type State
     *
     * @param OrderInterface $order
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function setInvoiceTypeState(OrderInterface $order): void
    {
        // canShip returns false when the order is currently in payment_review state so we must temporarily change
        // the state for canShip to return the desired value.
        $order->setState(Order::STATE_PROCESSING);

        // The order has not been shipped yet.
        if ($order->canShip()) {
            $this->setOrderState($order, Order::STATE_PROCESSING);
        } else {
            $this->setOrderState($order, Order::STATE_PAYMENT_REVIEW);
        }
    }

    /**
     * Process Vault Details
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     * @return void
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NotFoundException
     * @throws UnzerApiException
     */
    private function processVaultDetails(OrderInterface $order, PaymentResource $payment): void
    {
        if ($payment->getState() === PaymentState::STATE_CANCELED) {
            return;
        }

        $methodInstance = $order->getPayment()->getMethodInstance();
        if (!$methodInstance instanceof Base) {
            return;
        }

        if ($methodInstance->getVaultCode() === null) {
            return;
        }

        if ($methodInstance->isCreateVaultTokenOnSuccess() !== true) {
            return;
        }

        $transactionType = $payment->getInitialTransaction();
        if (!$transactionType instanceof AbstractTransactionType) {
            return;
        }

        $paymentMethodCode = $methodInstance->getCode();
        $paymentDataObject = $this->paymentDataObjectFactory->create($order->getPayment());

        $this->vaultDetailsHandlerManager->getHandlerByCode($paymentMethodCode)
            ->handle($paymentDataObject, $transactionType);
    }

    /**
     * Set Order State
     *
     * @param OrderInterface $order
     * @param string|null $state
     * @param string|null $status
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function setOrderState(OrderInterface $order, ?string $state = null, ?string $status = null): void
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

        // email already sent?
        if ($order->getEmailSent()) {
            return;
        }

        if (in_array($state, [Order::STATE_NEW, Order::STATE_CANCELED], true)) {
            return;
        }

        $this->sendEmails($order);
    }

    /**
     * Send Emails
     *
     * @param OrderInterface $order
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    protected function sendEmails(OrderInterface $order): void
    {
        // send order emails now, since we skipped them in Unzer\PAPI\Model\Command\Order
        // which is only used for canOrder methods
        if ($order->getPayment() instanceof OrderPaymentInterface
            && $order->getPayment()->getMethodInstance()->canOrder()
        ) {
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
