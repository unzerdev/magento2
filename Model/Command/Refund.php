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

        $storeCode = $order->getStore()->getCode();

        $client = $this->_getClient($storeCode, $payment->getMethodInstance());

        $hpPayment = $client->fetchPaymentByOrderId($order->getIncrementId());

        if ($hpPayment->isCanceled()) {
            return;
        }

        $amount = null;
        if (array_key_exists('amount', $commandSubject) && $commandSubject['amount'] !== null) {
            $amount = $this->getCreditMemoAmount($payment, (float)$commandSubject['amount']);
        }

        $cancellations = $client->cancelPayment($hpPayment, $amount, static::REASON);

        if (count($cancellations) > 0) {
            $lastCancellation = end($cancellations);
            $payment->setLastTransId($lastCancellation->getId());
        }
    }

    /**
     * Get Amount
     *
     * @param OrderPayment $payment
     * @param float $amount
     * @return float|null
     */
    protected function getCreditMemoAmount(OrderPayment $payment, float $amount): ?float
    {
        if ($this->_config->isCustomerCurrencyUsed()) {
            $amount = (float)$payment->getCreditmemo()->getGrandTotal();
        }

        return $amount;
    }
}
