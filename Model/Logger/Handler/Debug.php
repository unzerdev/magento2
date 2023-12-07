<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Unzer Module Debug Logger
 *
 * @link  https://docs.unzer.com/
 */
class Debug extends Base
{
    /** @var string */
    protected $fileName = '/var/log/unzer_debug.log';

    /** @var int */
    protected $loggerType = Logger::DEBUG;
}
