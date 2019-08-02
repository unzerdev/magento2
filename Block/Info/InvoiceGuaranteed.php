<?php

namespace Heidelpay\Gateway2\Block\Info;

class InvoiceGuaranteed extends Invoice
{
    protected $_template = 'Heidelpay_Gateway2::info/invoice_guaranteed.phtml';

    /**
     * @inheritDoc
     */
    public function toPdf()
    {
        $this->setTemplate('Heidelpay_Gateway2::info/pdf/invoice_guaranteed.phtml');
        return $this->toHtml();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    public function getCustomerSalutation()
    {
        return $this->_getPayment()->getCustomer()->getSalutation();
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    public function getCustomerBirthdate()
    {
        return $this->_getPayment()->getCustomer()->getBirthDate();
    }
}
