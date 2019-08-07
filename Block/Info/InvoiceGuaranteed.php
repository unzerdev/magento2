<?php

namespace Heidelpay\Gateway2\Block\Info;

class InvoiceGuaranteed extends Invoice
{
    protected $_template = 'Heidelpay_Gateway2::info/invoice_guaranteed.phtml';

    /**
     * @inheritDoc
     */
    public function toPdf(): string
    {
        $this->setTemplate('Heidelpay_Gateway2::info/pdf/invoice_guaranteed.phtml');
        return $this->toHtml();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    public function getCustomerSalutation(): string
    {
        return $this->_getPayment()->getCustomer()->getSalutation();
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    public function getCustomerBirthdate(): string
    {
        return $this->_getPayment()->getCustomer()->getBirthDate();
    }
}
