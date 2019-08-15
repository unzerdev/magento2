<?php

namespace Heidelpay\Gateway2\Model\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;

class CanVoidHandler implements ValueHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(array $subject, $storeId = null)
    {
        $payment = $subject['payment']->getPayment();
        return $payment instanceof Payment && ($payment->getAmountAuthorized() > 0 || $payment->getAmountPaid() > 0);
    }
}
