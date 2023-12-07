<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Handler for checking if payments can be refunded
 *
 * @link  https://docs.unzer.com/
 */
class CanRefundHandler implements ValueHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(array $subject, $storeId = null)
    {
        $payment = $subject['payment']->getPayment();
        if (!$payment instanceof Payment) {
            return false;
        }
        if ($payment->getBaseAmountPaid() > $payment->getBaseAmountRefunded()) {
            return true;
        }
        return false;
    }
}
