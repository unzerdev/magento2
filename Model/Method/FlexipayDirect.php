<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Model\Config;

class FlexipayDirect extends Base
{
    protected $_code = Config::METHOD_FLEXIPAY_DIRECT;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;
}
