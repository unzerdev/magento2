<?php

namespace Unzer\PAPI\Model\Command;

use Unzer\PAPI\Helper\Order as OrderHelper;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use Unzer\PAPI\Model\System\Config\Source\PaymentAction;
use UnzerSDK\Exceptions\UnzerApiException;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\Order\Payment\Operations\AuthorizeOperation;
use Magento\Sales\Model\Order\Payment\Operations\CaptureOperation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Order Command for payments
 *
 * Copyright (C) 2021 - today Unzer GmbH
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
 * @link  https://docs.unzer.com/
 *
 * @author Justin Nuß
 *
 * @package  unzerdev/magento2
 */
class Order extends AbstractCommand
{
    /**
     * @var AuthorizeOperation
     */
    protected $_authorizeOperation;

    /**
     * @var CaptureOperation
     */
    protected $_captureOperation;

    /**
     * Order constructor.
     * @param Session $checkoutSession
     * @param Config $config
     * @param LoggerInterface $logger
     * @param OrderHelper $orderHelper
     * @param UrlInterface $urlBuilder
     * @param AuthorizeOperation $authorizeOperation
     * @param CaptureOperation $captureOperation
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Session $checkoutSession,
        Config $config,
        LoggerInterface $logger,
        OrderHelper $orderHelper,
        UrlInterface $urlBuilder,
        AuthorizeOperation $authorizeOperation,
        CaptureOperation $captureOperation,
        StoreManagerInterface $storeManager

    ) {
        parent::__construct($checkoutSession, $config, $logger, $orderHelper, $urlBuilder, $storeManager);

        $this->_authorizeOperation = $authorizeOperation;
        $this->_captureOperation = $captureOperation;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws UnzerApiException
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        /** @var OrderPaymentInterface $payment */
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
                $this->_authorizeOperation->authorize($payment, true, $commandSubject['amount']);
                break;
            case PaymentAction::ACTION_AUTHORIZE_CAPTURE:
                $this->_captureOperation->capture($payment, null);
                break;
            default:
                throw new \Exception('Invalid payment action');
        }

        // Don't create a transaction for the Order command itself.
        $payment->setSkipOrderProcessing(true);

        return null;
    }
}
