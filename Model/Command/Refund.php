<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Refund Command for payments
 *
 * @link  https://docs.unzer.com/
 */
class Refund extends AbstractCommand
{
    private const METHOD_PREPAYMENT = 'unzer_prepayment';

    public const REASON = CancelReasonCodes::REASON_CODE_RETURN;

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function execute(array $commandSubject): void
    {
        /** @var OrderPayment $payment */
        $payment = $commandSubject['payment']->getPayment();

        $order = $payment->getOrder();

        $amount = array_key_exists('amount', $commandSubject) ? (float)$commandSubject['amount'] : null;

        $storeCode = $order->getStore()->getCode();

        $client = $this->_getClient($storeCode, $payment->getMethodInstance());

        $hpPayment = $client->fetchPaymentByOrderId($order->getIncrementId());

        if ($hpPayment->isCanceled()) {
            return;
        }

        // because of the nature of Prepayment, we need to refund the whole payment,
        // otherwise refund is not possible at all for prepayment.
        if ($payment->getMethodInstance()->getCode() === self::METHOD_PREPAYMENT) {
            $cancellations = $client->cancelPayment($hpPayment, $amount, static::REASON);

            if (count($cancellations) > 0) {
                $lastCancellation = end($cancellations);
                $payment->setLastTransId($lastCancellation->getId());
            }

        } else {

            $chargeId = $payment->getParentTransactionId();

            $cancellation = $client->cancelChargeById($hpPayment, $chargeId, $amount, self::REASON);

            $payment->setLastTransId($cancellation->getId());
        }
    }
}
