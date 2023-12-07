<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Config;

use Magento\Sales\Model\Order\Payment;

/**
 * Handler for checking if payments can be canceled
 *
 * @link  https://docs.unzer.com/
 */
class CanCancelHandler extends CanRefundHandler
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
        if ($payment->getBaseAmountAuthorized() > $payment->getBaseAmountCanceled() ||
            $payment->getBaseAmountPaid() > $payment->getBaseAmountCanceled()) {
            return true;
        }
        return false;
    }
}
