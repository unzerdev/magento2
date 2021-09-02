<?php

namespace Heidelpay\MGW\Model\Logger;

use UnzerSDK\Interfaces\DebugHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Wrapper around Psr Logger for use as Debug Handler in the heidelpay PHP SDK
 *
 * Copyright (C) 2019 heidelpay GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.heidelpay.com/
 *
 * @author Justin Nuß
 *
 * @package  heidelpay/magento2-merchant-gateway
 */
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
    public function log(string $message)
    {
        $this->_logger->debug($message);
    }
}
