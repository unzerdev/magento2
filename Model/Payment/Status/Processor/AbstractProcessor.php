<?php

declare(strict_types=1);

namespace Unzer\PAPI\Model\Payment\Status\Processor;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Unzer\PAPI\Helper\Payment as PaymentHelper;
use Unzer\PAPI\Model\Command\TransactionSynchronizer;
use Unzer\PAPI\Model\Payment\Status\OrderStateApplier;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment as PaymentResource;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

/**
 * Base implementation of {@see ProcessorInterface}. Holds the dispatch on
 * Unzer payment state and supplies the default per-state behavior; method
 * subclasses override only the hooks they need.
 *
 * Template Method pattern: process() is the fixed algorithm skeleton,
 * processCanceled/Completed/Chargeback/Partly/PaymentReview/Pending are
 * the protected hooks.
 *
 * @link  https://docs.unzer.com/
 */
abstract class AbstractProcessor implements ProcessorInterface
{
    /**
     * @var TransactionSynchronizer
     */
    protected TransactionSynchronizer $_transactionSynchronizer;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected InvoiceRepositoryInterface $_invoiceRepository;

    /**
     * @var OrderRepository
     */
    protected OrderRepository $_orderRepository;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected OrderPaymentRepositoryInterface $_paymentRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    protected TransactionRepositoryInterface $_transactionRepository;

    /**
     * @var OrderStateApplier
     */
    protected OrderStateApplier $_orderStateApplier;

    /**
     * @param TransactionSynchronizer $transactionSynchronizer
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderRepository $orderRepository
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderStateApplier $orderStateApplier
     */
    public function __construct(
        TransactionSynchronizer $transactionSynchronizer,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderRepository $orderRepository,
        OrderPaymentRepositoryInterface $paymentRepository,
        TransactionRepositoryInterface $transactionRepository,
        OrderStateApplier $orderStateApplier
    ) {
        $this->_transactionSynchronizer = $transactionSynchronizer;
        $this->_invoiceRepository = $invoiceRepository;
        $this->_orderRepository = $orderRepository;
        $this->_paymentRepository = $paymentRepository;
        $this->_transactionRepository = $transactionRepository;
        $this->_orderStateApplier = $orderStateApplier;
    }

    /**
     * @inheritDoc
     *
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     * @throws Exception
     */
    public function process(OrderInterface $order, PaymentResource $payment): void
    {
        switch ($payment->getState()) {
            case PaymentState::STATE_CANCELED:
                $this->processCanceled($order, $payment);
                break;
            case PaymentState::STATE_COMPLETED:
                $this->processCompleted($order, $payment);
                break;
            case PaymentState::STATE_CHARGEBACK:
                $this->processChargeback($order, $payment);
                break;
            case PaymentState::STATE_PARTLY:
                $this->processPartly($order, $payment);
                break;
            case PaymentState::STATE_PAYMENT_REVIEW:
                $this->processPaymentReview($order);
                break;
            case PaymentState::STATE_PENDING:
                $this->processPending($order, $payment);
                break;
        }
    }

    /**
     * Default behavior for Unzer state CANCELED: cancel open invoices,
     * cancel the order if Magento still allows it, and close the order
     * when the full amount has been canceled.
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     */
    protected function processCanceled(OrderInterface $order, PaymentResource $payment): void
    {
        $this->_transactionSynchronizer->applyCancellationOnMagento($order, $payment);

        // Orders in payment_review can't be cancelled, so we must manually
        // change the state so that we can cancel the order.
        if ($order->isPaymentReview()) {
            $order->setState(Order::STATE_PROCESSING);
        }

        // If the payment was voided, we do not want to cancel the whole order and invoice.
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

        $amount = $payment->getAmount();
        if ($amount->getTotal() && $amount->getTotal() === $amount->getCanceled()) {
            $this->_orderStateApplier->setOrderState($order, Order::STATE_CLOSED, Order::STATE_CLOSED);
            $this->_orderRepository->save($order);
        }
    }

    /**
     * Default behavior for Unzer state COMPLETED: register the capture,
     * pay any open invoice matched by transaction id, close the payment
     * transaction (and its parent), and resolve to STATE_PROCESSING.
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     */
    protected function processCompleted(OrderInterface $order, PaymentResource $payment): void
    {
        $this->_transactionSynchronizer->applyCaptureOnMagento($order, $payment);

        $orderPayment = $order->getPayment();
        $transactionId = $orderPayment->getLastTransId();

        /** @var Order\Invoice|null $invoice */
        $invoice = $order->getInvoiceCollection()->getItemByColumnValue('transaction_id', $transactionId);

        if ($invoice !== null && (int)$invoice->getState() === Order\Invoice::STATE_OPEN) {
            $invoice->pay();

            $order = $invoice->getOrder();
            $orderPayment = $order->getPayment();

            $this->_invoiceRepository->save($invoice);
            $this->_orderRepository->save($order);
            $this->_paymentRepository->save($orderPayment);
        }

        /** @var Order\Payment\Transaction|false $paymentTransaction */
        $paymentTransaction = $this->_transactionRepository->getByTransactionId(
            $transactionId,
            $orderPayment->getId(),
            $order->getId()
        );

        if ($paymentTransaction && !$paymentTransaction->getIsClosed()) {
            $paymentTransaction->setIsClosed(true);
            $this->_transactionRepository->save($paymentTransaction);

            $parentTxnId = $paymentTransaction->getParentTxnId();
            if ($parentTxnId !== '') {
                try {
                    /** @var Order\Payment\Transaction|false $parentPaymentTransaction */
                    $parentPaymentTransaction = $this->_transactionRepository->getByTransactionId(
                        $parentTxnId,
                        $orderPayment->getId(),
                        $order->getId()
                    );
                    if ($parentPaymentTransaction && !$parentPaymentTransaction->getIsClosed()) {
                        $parentPaymentTransaction->setIsClosed(true);
                        $this->_transactionRepository->save($parentPaymentTransaction);
                    }
                } catch (InputException $e) {
                    // No parent row stored for this txn_id — non-fatal.
                }
            }
        }

        // Pin to processing so the state resolver doesn't leave the order
        // in payment_review (which can happen for invoice-type methods).
        $order->setState(Order::STATE_PROCESSING);

        $this->_orderStateApplier->setOrderState($order);
    }

    /**
     * Default behavior for Unzer state CHARGEBACK: register the refund
     * notification and flag the order for fraud review unless it has
     * already been canceled/closed.
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     *
     * @return void
     * @throws Exception
     */
    protected function processChargeback(OrderInterface $order, PaymentResource $payment): void
    {
        $this->_transactionSynchronizer->applyChargebackOnMagento($order, $payment);

        if ($order->getState() !== Order::STATE_CANCELED && $order->getState() !== Order::STATE_CLOSED) {
            $this->_orderStateApplier->setOrderState($order, Order::STATE_PAYMENT_REVIEW, Order::STATUS_FRAUD);
        }
    }

    /**
     * Default behavior for Unzer state PARTLY: synchronize any partial
     * cancellation and capture transactions, then resolve the order
     * state via the state resolver. Method subclasses override this for
     * payment-method-specific status mapping (see PrepaymentProcessor).
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     */
    protected function processPartly(OrderInterface $order, PaymentResource $payment): void
    {
        $this->_transactionSynchronizer->applyCancellationOnMagento($order, $payment);
        $this->_transactionSynchronizer->applyCaptureOnMagento($order, $payment);

        $this->_orderStateApplier->setOrderState($order);
    }

    /**
     * Default behavior for Unzer state PAYMENT_REVIEW.
     *
     * @param OrderInterface $order
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function processPaymentReview(OrderInterface $order): void
    {
        $this->_orderStateApplier->setOrderState($order, Order::STATE_PAYMENT_REVIEW);
    }

    /**
     * Default behavior for Unzer state PENDING: when an authorization
     * already succeeded, jump straight to processing/ready-to-capture;
     * for invoice-type methods that haven't shipped, fall back to the
     * canShip-aware invoice-type state.
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     */
    protected function processPending(OrderInterface $order, PaymentResource $payment): void
    {
        $authorization = $payment->getAuthorization();

        if ($authorization !== null && $authorization->isSuccess() && $order->getState() !== Order::STATE_PROCESSING) {
            $this->_orderStateApplier->setOrderState(
                $order,
                Order::STATE_PROCESSING,
                PaymentHelper::STATUS_READY_TO_CAPTURE
            );
            return;
        }

        $paymentType = $payment->getPaymentType();
        if ($paymentType instanceof BasePaymentType && $paymentType->isInvoiceType()) {
            $this->setInvoiceTypeState($order);
        }
    }

    /**
     * canShip returns false while the order is in payment_review, so we
     * temporarily flip to processing to ask the right question; the
     * answer then drives whether the final state is processing or back
     * to payment_review.
     *
     * @param OrderInterface $order
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function setInvoiceTypeState(OrderInterface $order): void
    {
        $order->setState(Order::STATE_PROCESSING);

        if ($order->canShip()) {
            $this->_orderStateApplier->setOrderState($order, Order::STATE_PROCESSING);
        } else {
            $this->_orderStateApplier->setOrderState($order, Order::STATE_PAYMENT_REVIEW);
        }
    }

    /**
     * Whether a void transaction has already been recorded against the
     * order's payment. Used to avoid double-cancelling on the canceled
     * state path.
     *
     * @param OrderInterface $order
     *
     * @return bool
     * @throws InputException
     */
    protected function isOrderVoided(OrderInterface $order): bool
    {
        $voidedPaymentTransaction = $this->_transactionRepository->getByTransactionType(
            TransactionInterface::TYPE_VOID,
            $order->getPayment()->getId()
        );

        return (bool)$voidedPaymentTransaction;
    }
}
