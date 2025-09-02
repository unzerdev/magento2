<?php

namespace Unzer\PAPI\Model\Command;

use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment as UnzerPayment;
use UnzerSDK\Resources\TransactionTypes\Authorization as UnzerAuthorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation as UnzerCancellation;
use UnzerSDK\Resources\TransactionTypes\Charge as UnzerCharge;

class TransactionSynchronizer
{
    private OrderPaymentRepositoryInterface $paymentRepository;
    private OrderRepositoryInterface $orderRepository;
    private TransactionRepositoryInterface $transactionRepository;

    /**
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderPaymentRepositoryInterface $paymentRepository
     */
    public function __construct(
        TransactionRepositoryInterface  $transactionRepository,
        OrderRepositoryInterface        $orderRepository,
        OrderPaymentRepositoryInterface $paymentRepository
    )
    {
        $this->transactionRepository = $transactionRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @param Order $order
     * @param UnzerPayment $unzer
     * @param string $chargeId
     *
     * @return void
     *
     * @throws UnzerApiException
     */
    public function applyCaptureById(Order $order, UnzerPayment $unzer, string $chargeId): void
    {
        if ($chargeId === '') {
            return;
        }

        $payment = $this->getOrderPayment($order);
        if (!$payment) {
            return;
        }

        foreach ((array)($unzer->getCharges() ?? []) as $charge) {
            if (!$charge || !$charge->isSuccess()) {
                continue;
            }
            if ((string)($charge->getId() ?? '') !== $chargeId) {
                continue;
            }

            if ($this->hasTransaction($payment, $order, $chargeId)) {
                return;
            }

            $parentTxnId = $this->resolveCaptureParentTxnId($unzer);
            if ($parentTxnId) {
                $payment->setParentTransactionId($parentTxnId);
            }
            $payment->setTransactionId($chargeId);

            $amount = (float)($charge->getAmount() ?? 0.0);
            if ($amount <= 0.0) {
                $amount = (float)$order->getBaseGrandTotal();
            }

            $payment->registerCaptureNotification($amount, true);
            $this->paymentRepository->save($payment);
            $this->orderRepository->save($order);

            return;
        }
    }

    /**
     * @param Order $order
     * @param UnzerPayment $unzer
     * @param string $cancellationId
     *
     * @return void
     */
    public function applyRefundById(Order $order, UnzerPayment $unzer, string $cancellationId): void
    {
        if ($cancellationId === '') {
            return;
        }

        $payment = $this->getOrderPayment($order);
        if (!$payment) {
            return;
        }

        foreach ((array)($unzer->getCharges() ?? []) as $chg) {
            if (!$chg || !method_exists($chg, 'getCancellations')) {
                continue;
            }

            foreach ((array)($chg->getCancellations() ?? []) as $cnl) {
                if (!$cnl || !$cnl->isSuccess()) {
                    continue;
                }
                $refundId = (string)($cnl->getId() ?? '');
                if ($refundId !== $cancellationId) {
                    continue;
                }

                if ($this->hasTransaction($payment, $order, $refundId)) {
                    return;
                }

                $parentChargeId = $this->resolveRefundParentTxnId($cnl);
                if (!$parentChargeId) {
                    return;
                }

                $amount = (float)($cnl->getAmount() ?? 0.0);
                if ($amount <= 0.0) {
                    return;
                }

                $payment->setParentTransactionId($parentChargeId);
                $payment->setTransactionId($refundId);
                $payment->registerRefundNotification($amount);

                $this->paymentRepository->save($payment);
                $this->orderRepository->save($order);

                return;
            }
        }
    }

    /**
     * @param Order $order
     *
     * @return OrderPayment|null
     */
    private function getOrderPayment(Order $order): ?OrderPayment
    {
        $payment = $order->getPayment();
        return $payment instanceof OrderPayment ? $payment : null;
    }

    /**
     * @param OrderPayment $payment
     * @param Order $order
     * @param string $txnId
     *
     * @return bool
     */
    private function hasTransaction(OrderPayment $payment, Order $order, string $txnId): bool
    {
        if ($txnId === '') {
            return false;
        }
        try {
            return (bool)$this->transactionRepository->getByTransactionId(
                $txnId,
                (int)$payment->getId(),
                (int)$order->getId()
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

    /**
     * @param UnzerCancellation $cnl
     *
     * @return string|null
     */
    private function resolveRefundParentTxnId(UnzerCancellation $cnl): ?string
    {
        $parent = $cnl->getParentResource();
        if ($parent instanceof UnzerCharge && $parent->getId()) {
            return (string)$parent->getId();
        }
        return null;
    }
}
