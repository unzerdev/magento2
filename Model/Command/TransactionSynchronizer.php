<?php

namespace Unzer\PAPI\Model\Command;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment as UnzerPayment;
use UnzerSDK\Resources\TransactionTypes\Authorization as UnzerAuthorization;
use UnzerSDK\Resources\TransactionTypes\Charge as UnzerCharge;

class TransactionSynchronizer
{
    private OrderPaymentRepositoryInterface $paymentRepository;
    private TransactionRepositoryInterface $transactionRepository;

    /**
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderPaymentRepositoryInterface $paymentRepository
     */
    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        OrderPaymentRepositoryInterface $paymentRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @param OrderInterface $order
     * @param UnzerPayment $unzer
     *
     * @return void
     *
     * @throws UnzerApiException
     */
    public function applyCaptureOnMagento(OrderInterface $order, UnzerPayment $unzer): void
    {
        $payment = $this->getOrderPayment($order);
        $capture = array_last($unzer->getCharges());

        if (!$payment || !$capture) {
            return;
        }

        $captureId = $capture->getId() ?? '';

        if ($captureId === '') {
            return;
        }

        if ($this->hasTransaction($payment, $order, $captureId)) {
            return;
        }

        $parentTxnId = $this->resolveCaptureParentTxnId($unzer);

        if ($parentTxnId) {
            $payment->setParentTransactionId($parentTxnId);
        }

        $payment->setTransactionId($captureId);

        if ($capture->isSuccess()) {
            $payment->registerCaptureNotification($capture->getAmount(), true);
            $this->paymentRepository->save($payment);
        }
        $this->paymentRepository->save($payment);
    }

    /**
     * @param OrderInterface $order
     * @param UnzerPayment $unzer
     *
     * @return void
     *
     * @throws UnzerApiException
     */
    public function applyCancellationOnMagento(OrderInterface $order, UnzerPayment $unzer): void
    {
        $payment = $this->getOrderPayment($order);
        $cancellation = array_last($unzer->getCancellations());

        if (!$payment || !$cancellation) {
            return;
        }

        $cancelId = $cancellation->getId() ?? '';

        if ($cancelId === '') {
            return;
        }

        if ($this->hasTransaction($payment, $order, $cancelId)) {
            return;
        }

        $parent = $cancellation->getParentResource();

        if ($parent instanceof UnzerCharge && $parent->getId()) {
            $parentTxnId = $parent->getId();

            // prepayment
            if ($unzer->getAmount()->getTotal() == 0) {
                $cancelTxnId = $parentTxnId . '-void';
                $payment->setParentTransactionId($parentTxnId);
                $payment->setTransactionId($cancelTxnId);
                $payment->registerVoidNotification($cancellation->getAmount());

                return;
            }

            $refundTxnId = $parentTxnId . '-refund';

            $payment->setParentTransactionId($parentTxnId);
            $payment->setTransactionId($refundTxnId);

            $payment->registerRefundNotification($cancellation->getAmount());

            $this->paymentRepository->save($payment);

            return;
        }

        if ($parent instanceof UnzerAuthorization && $parent->getId()) {
            $parentTxnId = $parent->getId();
            $cancelTxnId = $parentTxnId . '-void';

            $payment->setParentTransactionId($parentTxnId);
            $payment->setTransactionId($cancelTxnId);

            $payment->registerVoidNotification($cancellation->getAmount());

            $this->paymentRepository->save($payment);
        }
    }

    /**
     * @param OrderInterface $order
     *
     * @return OrderPayment|null
     */
    private function getOrderPayment(OrderInterface $order): ?OrderPayment
    {
        $payment = $order->getPayment();

        return $payment instanceof OrderPayment ? $payment : null;
    }

    /**
     * @param OrderPayment $payment
     * @param OrderInterface $order
     * @param string $txnId
     *
     * @return bool
     */
    private function hasTransaction(OrderPayment $payment, OrderInterface $order, string $txnId): bool
    {
        if ($txnId === '') {
            return false;
        }
        try {
            return (bool)$this->transactionRepository->getByTransactionId(
                $txnId,
                $payment->getId(),
                $order->getId()
            );
        } catch (\Throwable $ex) {
            return false;
        }
    }

    /**
     * @param UnzerPayment $unzer
     *
     * @return string|null
     *
     * @throws UnzerApiException
     */
    private function resolveCaptureParentTxnId(UnzerPayment $unzer): ?string
    {
        $parent = $unzer->getAuthorization(true);
        if ($parent instanceof UnzerAuthorization && $parent->getId()) {
            return (string)$parent->getId();
        }

        return null;
    }
}
