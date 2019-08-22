<?php

namespace Heidelpay\MGW\Model\Command;

use Heidelpay\MGW\Helper\Order;
use Heidelpay\MGW\Model\Config;
use heidelpayPHP\Heidelpay;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\CommandInterface;

/**
 * Abstract Command for using the heidelpay SDK
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
abstract class AbstractCommand implements CommandInterface
{
    /**
     * @var Heidelpay
     */
    protected $_client;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var Order
     */
    protected $_orderHelper;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * AbstractCommand constructor.
     * @param Config $config
     * @param Order $orderHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Config $config,
        Order $orderHelper,
        UrlInterface $urlBuilder
    ) {
        $this->_config = $config;
        $this->_orderHelper = $orderHelper;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Returns the URL to which customers are redirected after payment.
     *
     * @return string
     */
    protected function _getCallbackUrl(): string
    {
        return $this->_urlBuilder->getUrl('hpmgw/payment/callback');
    }

    /**
     * @return Heidelpay
     * @throws NoSuchEntityException
     */
    protected function _getClient(): Heidelpay
    {
        if ($this->_client === null) {
            $this->_client = $this->_config->getHeidelpayClient();
        }

        return $this->_client;
    }
}
