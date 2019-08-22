<?php

namespace Heidelpay\MGW\Model\Command;

use Heidelpay\MGW\Helper\Order as OrderHelper;
use Heidelpay\MGW\Model\Config;
use Heidelpay\MGW\Model\Method\Base;
use Heidelpay\MGW\Model\System\Config\Source\PaymentAction;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order as OrderModel;

/**
 * Order Command for payments
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
class Order extends AbstractCommand
{
    /**
     * @var Authorize
     */
    protected $_authorizeCommand;

    /**
     * @var Capture
     */
    protected $_captureCommand;

    /**
     * Order constructor.
     * @param Config $config
     * @param OrderHelper $orderHelper
     * @param UrlInterface $urlBuilder
     * @param Authorize $authorizeCommand
     * @param Capture $captureCommand
     */
    public function __construct(
        Config $config,
        OrderHelper $orderHelper,
        UrlInterface $urlBuilder,
        Authorize $authorizeCommand,
        Capture $captureCommand
    ) {
        parent::__construct($config, $orderHelper, $urlBuilder);

        $this->_authorizeCommand = $authorizeCommand;
        $this->_captureCommand = $captureCommand;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws HeidelpayApiException
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var OrderModel $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        /** @var Base $method */
        $method = $payment->getMethodInstance();

        /** @var string|null $action */
        $action = $method->getConfigData('order_payment_action');

        switch ($action) {
            case PaymentAction::ACTION_AUTHORIZE:
                $this->_authorizeCommand->execute($commandSubject);
                break;
            case PaymentAction::ACTION_AUTHORIZE_CAPTURE:
                $this->_captureCommand->execute($commandSubject);
                break;
            default:
                throw new \Exception('Invalid payment action');
        }

        return null;
    }
}
