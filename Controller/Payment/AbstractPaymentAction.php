<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order;

/**
 * Abstract action for accessing the current order and payment
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
abstract class AbstractPaymentAction extends Action
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * AbstractPaymentAction constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param Config $moduleConfig
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $moduleConfig
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleConfig = $moduleConfig;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        /** @var Order $order */
        $order = $this->_checkoutSession->getLastRealOrder();

        /** @var HttpInterface $response */
        $response = $this->getResponse();

        if (!$order || !$order->getId()) {
            $response->setHttpResponseCode(400);
            return $response;
        }

        try {
            /** @var Payment $payment */
            $payment = $this->_moduleConfig
                ->getHeidelpayClient()
                ->fetchPaymentByOrderId($order->getIncrementId());
        } catch (HeidelpayApiException $e) {
            if ($e->getCode() === ApiResponseCodes::API_ERROR_PAYMENT_NOT_FOUND) {
                $response->setHttpResponseCode(404);
            } else {
                $response->setHttpResponseCode(500);
            }

            $response->setBody($e->getClientMessage());
            return $response;
        } catch (\Exception $e) {
            $response->setHttpResponseCode(500);
            $response->setBody('Internal server error');
            return $response;
        }

        return $this->executeWith($order, $payment);
    }

    /**
     * @param Order $order
     * @param Payment $Payment
     * @return ResultInterface|ResponseInterface
     */
    abstract public function executeWith(Order $order, Payment $Payment);
}
