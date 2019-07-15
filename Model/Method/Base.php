<?php

namespace Heidelpay\Gateway2\Model\Method;

use Magento\Payment\Model\Method\AbstractMethod;

class Base extends AbstractMethod
{
    protected $_code = \Heidelpay\Gateway2\Config\Method\Creditcard::METHOD_CODE;
}