<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Model\Config;

class InvoiceGuaranteed extends Invoice
{
    protected $_code = Config::METHOD_INVOICE_GUARANTEED;

    protected $_infoBlockType = \Heidelpay\Gateway2\Block\Info\InvoiceGuaranteed::class;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;
}
