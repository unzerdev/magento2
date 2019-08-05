<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Model\Config;

class DirectDebitGuaranteed extends DirectDebit
{
    protected $_code = Config::METHOD_DIRECT_DEBIT_GUARANTEED;
}
