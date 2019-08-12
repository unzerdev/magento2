<?php

namespace Heidelpay\Gateway2\Model\Logger;

use heidelpayPHP\Interfaces\DebugHandlerInterface;
//use \Psr\Log\LoggerInterface;

class DebugHandler implements DebugHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * DebugHandler constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_logger = $logger;
    }

    /**
     * Export in log file debug data.
     *
     * @param string $message Debug message.
     *
     * @return void
     */
    public function log(string $message)
    {
        $this->_logger->debug($message);
    }
}
