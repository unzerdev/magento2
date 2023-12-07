<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Cancel Command for payments
 *
 * @link  https://docs.unzer.com/
 */
class Cancel extends AbstractCommand
{
    public const REASON = CancelReasonCodes::REASON_CODE_CANCEL;

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

        $amount = $this->getOrderAmount($payment);

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
     * @return float|null
     */
    protected function getOrderAmount(OrderPayment $payment): ?float
    {
        if ($this->_config->isCustomerCurrencyUsed()) {
            return (float)$payment->getOrder()->getGrandTotal();
        }
        return (float)$payment->getOrder()->getBaseGrandTotal();
    }
}
