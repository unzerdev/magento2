<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Logger;

use UnzerSDK\Interfaces\DebugHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Wrapper around Psr Logger for use as Debug Handler in the Unzer PHP SDK
 *
 * @link  https://docs.unzer.com/
 */
class DebugHandler implements DebugHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $_logger;

    /**
     * DebugHandler constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
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
    public function log(string $message): void
    {
        $this->_logger->debug($message);
    }
}
