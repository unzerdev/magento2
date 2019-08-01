<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Model\Config;

class InvoiceGuaranteed extends Base
{
    protected $_code = Config::METHOD_INVOICE_GUARANTEED;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;
}
