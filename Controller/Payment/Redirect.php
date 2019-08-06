<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Magento\Sales\Model\Order;

class Redirect extends AbstractPaymentAction
{
    /**
     * @inheritDoc
     */
    public function executeWith(Order $order, Payment $payment)
    {
        $transaction = $payment->getAuthorization();

        if (!$transaction instanceof Authorization) {
            $transaction = $payment->getChargeByIndex(0);
        }

        /** @var string|null $redirectUrl */
        $redirectUrl = $transaction->getRedirectUrl();

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($redirectUrl);
        return $redirect;
    }
}
