<?php

namespace Heidelpay\Gateway2\Model\Logger;

use heidelpayPHP\Interfaces\DebugHandlerInterface;


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
    public function __construct(Logger $logger)
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
        $this->_logger->debug(var_export($message, true));
    }
}