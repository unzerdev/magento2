<?php

namespace Heidelpay\MGW\Controller\Payment;

use Exception;
use Heidelpay\MGW\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Model\Order;

/**
 * Callback action called when customers return from a payment provider
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
class Callback extends AbstractPaymentAction
{
    /**
     * @var CartManagementInterface
     */
    protected $_cartManagement;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Heidelpay\MGW\Helper\Payment
     */
    protected $_paymentHelper;

    /**
     * Callback constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     * @param Config $moduleConfig
     * @param \Heidelpay\MGW\Helper\Payment $paymentHelper
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        Config $moduleConfig,
        \Heidelpay\MGW\Helper\Payment $paymentHelper
    )
    {
        parent::__construct($context, $checkoutSession, $moduleConfig);

        $this->_cartManagement = $cartManagement;
        $this->_messageManager = $messageManager;
        $this->_paymentHelper = $paymentHelper;
    }

    /**
     * @inheritDoc
     * @throws HeidelpayApiException
     */
    public function executeWith(Order $order, Payment $payment)
    {
        /** @var Authorization|Charge|AbstractHeidelpayResource $resource */
        $resource = $payment->getAuthorization() ?? $payment->getChargeByIndex(0);

        if ($resource->isSuccess()) {
            $response = $this->handleSuccess($order, $resource);
        } elseif ($resource->isPending()) {
            $response = $this->handlePending($order);
        } else {
            $response = $this->handleError($order, $resource);
        }

        return $response;
    }

    /**
     * @param Order $order
     * @param Authorization|Charge|AbstractHeidelpayResource $resource
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws HeidelpayApiException
     */
    protected function handleError(
        Order $order,
        AbstractHeidelpayResource $resource
    ): \Magento\Framework\Controller\Result\Redirect
    {
        return $this->handleErrorMessage($order, $resource, $resource->getMessage()->getCustomer());
    }

    /**
     * @param Order $order
     * @param Authorization|Charge|AbstractHeidelpayResource $resource
     * @param string $message
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws HeidelpayApiException
     */
    private function handleErrorMessage(
        Order $order,
        AbstractHeidelpayResource $resource,
        string $message
    ): \Magento\Framework\Controller\Result\Redirect
    {
        $this->_checkoutSession->restoreQuote();
        $this->_messageManager->addErrorMessage($message);

        $this->_paymentHelper->handleTransactionError($order, $resource);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart');
        return $redirect;
    }

    /**
     * @param Order $order
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handlePending(Order $order): \Magento\Framework\Controller\Result\Redirect
    {
        $this->_paymentHelper->handleTransactionPending($order);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }

    /**
     * @param Order $order
     * @param Authorization|Charge|AbstractHeidelpayResource $resource
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws HeidelpayApiException
     */
    protected function handleSuccess(
        Order $order,
        AbstractHeidelpayResource $resource
    ): \Magento\Framework\Controller\Result\Redirect
    {
        try {
            $payment = $resource->getPayment();

            if ($payment->isCompleted()) {
                $this->_paymentHelper->handlePaymentCompletion($order, $payment);
            } else {
                $this->_paymentHelper->handleTransactionSuccess($order);
            }
        } catch (Exception $e) {
            return $this->handleErrorMessage($order, $resource, $e->getMessage());
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }
}
