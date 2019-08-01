<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Sales\Model\Order;

class Invoice extends Base
{
    protected $_code = Config::METHOD_INVOICE;

    protected $_infoBlockType = \Heidelpay\Gateway2\Block\Info\Invoice::class;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @inheritDoc
     */
    public function getAdditionalPaymentInformation(Order $order)
    {
        /** @var Payment $payment */
        $payment = $this->_getClient()->fetchPaymentByOrderId(
            $this->_orderHelper->getExternalId($order)
        );

        /** @var Charge|null $charge */
        $charge = $payment->getChargeByIndex(0);

        if ($charge === null) {
            return '';
        }

        return __(
            'Please transfer the amount of <strong>%1 %2</strong> '
            . 'to the following account after your order has arrived:<br /><br />'
            . 'Holder: %3<br/>IBAN: %4<br/>BIC: %5<br/><br/><i>'
            . 'Please use only this identification number as the descriptor :</i><br/><strong>%6</strong>',
            $charge->getAmount(), // TODO: format
            $charge->getCurrency(),
            $charge->getHolder(),
            $charge->getIban(),
            $charge->getBic(),
            $charge->getShortId()
        );
    }
}