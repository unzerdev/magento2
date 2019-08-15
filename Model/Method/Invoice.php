<?php

namespace Heidelpay\Gateway2\Model\Method;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order;

class Invoice extends Base
{
    /**
     * @inheritDoc
     */
    public function getAdditionalPaymentInformation(Order $order): string
    {
        /** @var Payment $payment */
        $payment = $this->_getClient()->fetchPaymentByOrderId($order->getIncrementId());

        /** @var Charge|null $charge */
        $charge = $payment->getChargeByIndex(0);

        if ($charge === null) {
            return '';
        }

        $formattedAmount = $this->_priceCurrency->format(
            $charge->getAmount(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $order->getStoreId(),
            $order->getOrderCurrency()
        );

        return __(
            'Please transfer the amount of %1 to the following account after your order has arrived:<br /><br />'
            . 'Holder: %2<br/>'
            . 'IBAN: %3<br/>'
            . 'BIC: %4<br/><br/>'
            . '<i>Please use only this identification number as the descriptor: </i><br/>'
            . '%5',
            $formattedAmount,
            $charge->getHolder(),
            $charge->getIban(),
            $charge->getBic(),
            $charge->getShortId()
        );
    }
}
