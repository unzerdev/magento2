<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Payment\Status\Processor;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment as PaymentResource;

/**
 * Status processor for Direct Bank Transfer (unzer_open_banking).
 *
 * Direct Bank Transfer can report payment.state = COMPLETED while the
 * latest charge transaction is still isPending() (bank initiation
 * succeeded; settlement hasn't arrived). In that case we must keep the
 * order in STATE_NEW / pending and leave the invoice OPEN — only when
 * the settlement charge-success webhook lands and the latest charge
 * flips to isSuccess() may we promote to STATE_PROCESSING and mark the
 * invoice PAID via the inherited default behavior.
 *
 * @link  https://docs.unzer.com/
 */
class OpenBankingProcessor extends AbstractProcessor
{
    private const STATUS_PENDING = 'pending';

    /**
     * @inheritDoc
     *
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     */
    protected function processCompleted(OrderInterface $order, PaymentResource $payment): void
    {
        if ($this->isLatestChargePending($payment)) {
            $this->_orderStateApplier->setOrderState($order, Order::STATE_NEW, self::STATUS_PENDING);

            return;
        }

        parent::processCompleted($order, $payment);
    }

    /**
     * Open Banking is neither an invoice-type nor an authorize-first
     * method, so the inherited processPending would no-op and leave the
     * transient STATE_PENDING_PAYMENT set by Controller/Payment/Redirect.
     * Force STATE_NEW / pending instead so the customer-visible status
     * matches the actual waiting-for-settlement reality.
     *
     * @inheritDoc
     *
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function processPending(OrderInterface $order, PaymentResource $payment): void
    {
        $this->_orderStateApplier->setOrderState($order, Order::STATE_NEW, self::STATUS_PENDING);
    }

    /**
     * Selects the most recent charge with the same rule as
     * TransactionSynchronizer::applyCaptureOnMagento (Model/Command/TransactionSynchronizer.php:46),
     * so both stay consistent. Absence of any charge is treated as
     * "still pending" — the payment exists but settlement hasn't begun.
     *
     * @param PaymentResource $payment
     *
     * @return bool
     */
    private function isLatestChargePending(PaymentResource $payment): bool
    {
        $charges = $payment->getCharges();
        if (empty($charges)) {
            return true;
        }

        $latestCharge = $charges[array_key_last($charges)];

        return $latestCharge->isPending();
    }
}
