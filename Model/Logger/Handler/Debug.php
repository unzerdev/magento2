<?php

namespace Heidelpay\Gateway2\Model\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Debug extends Base
{
    /** @var string */
    protected $fileName = '/var/log/heidelpay/debug.log';
    /** @var int */
    protected $loggerType = Logger::DEBUG;
}
