<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Traits\HasStates;
use Magento\Sales\Model\Order;

class AuthorizationCallback extends AbstractCallback
{
    /**
     * @param Order $order
     * @param Payment $payment
     * @return HasStates
     * @throws HeidelpayApiException
     */
    protected function getStateForPayment(Order $order, Payment $payment)
    {
        return $payment->getAuthorization();
    }
}