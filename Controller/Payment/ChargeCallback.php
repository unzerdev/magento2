<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Traits\HasStates;
use Magento\Sales\Model\Order;

class ChargeCallback extends AbstractCallback
{
    /**
     * @param Order $order
     * @param Payment $payment
     * @return HasStates
     */
    protected function getTransaction(Order $order, Payment $payment)
    {
        /** @var Charge[] $charges */
        $charges = $payment->getCharges();

        return end($charges);
    }
}