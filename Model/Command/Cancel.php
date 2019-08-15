<?php

namespace Heidelpay\Gateway2\Model\Command;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;

class Cancel extends AbstractCommand
{
    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws HeidelpayApiException
     */
    public function execute(array $commandSubject)
    {
        /** @var InfoInterface $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var Payment $hpPayment */
        $hpPayment = $this->_getClient()->fetchPaymentByOrderId($order->getIncrementId());

        /** @var Cancellation $cancellation */
        $cancellation = $hpPayment->cancel($order->getGrandTotal());

        if ($cancellation->isError()) {
            throw new LocalizedException(__('Failed to cancel payment.'));
        }
    }
}
