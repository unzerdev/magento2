<?php

namespace Heidelpay\MGW\Controller\Payment;

use Exception;
use Heidelpay\MGW\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Traits\HasStates;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment as OrderPayment;

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
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $_paymentRepository;

    /**
     * Callback constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     * @param Config $moduleConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param OrderPaymentRepositoryInterface $paymentRepository
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        Config $moduleConfig,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        OrderPaymentRepositoryInterface $paymentRepository
    ) {
        parent::__construct($context, $checkoutSession, $moduleConfig);
        $this->_cartManagement = $cartManagement;
        $this->_messageManager = $messageManager;
        $this->_moduleConfig = $moduleConfig;
        $this->_orderRepository = $orderRepository;
        $this->_orderSender = $orderSender;
        $this->_paymentRepository = $paymentRepository;
    }

    /**
     * @inheritDoc
     * @throws HeidelpayApiException
     */
    public function executeWith(Order $order, Payment $payment)
    {
        /** @var AbstractHeidelpayResource|HasStates $transaction */
        $transaction = $payment->getAuthorization() ?? $payment->getChargeByIndex(0);

        if ($payment->isCompleted()) {
            $response = $this->handleSuccess($order, $transaction);
        } elseif ($payment->isPending()) {
            $response = $this->handlePending($order);
        } else {
            $response = $this->handleError($order, $transaction);
        }

        return $response;
    }

    /**
     * @param Order $order
     * @param AbstractHeidelpayResource $transaction
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handleError(
        Order $order,
        AbstractHeidelpayResource $transaction
    ): \Magento\Framework\Controller\Result\Redirect {
        $this->_checkoutSession->restoreQuote();
        $order->cancel();
        $this->_orderRepository->save($order);

        try {
            $this->_messageManager->addError($transaction->getMessage()->getCustomer());
        } catch (HeidelpayApiException $e) {
            $this->_messageManager->addError($e->getMerchantMessage());
        } catch (Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart');
        return $redirect;
    }

    /**
     * @param Order $order
     * @param AbstractHeidelpayResource $transaction
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handlePending(
        Order $order,
        AbstractHeidelpayResource $transaction
    ): \Magento\Framework\Controller\Result\Redirect
    {
        $order->setState(Order::STATE_PAYMENT_REVIEW);
        $order->addCommentToStatusHistory(sprintf("Transaction %s is pending", $transaction->getUniqueId()));
        $this->_orderRepository->save($order);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }

    /**
     * @param Order $order
     * @param AbstractHeidelpayResource $transaction
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handleSuccess(
        Order $order,
        AbstractHeidelpayResource $transaction
    ): \Magento\Framework\Controller\Result\Redirect {
        /** @var OrderPayment $payment */
        $payment = $order->getPayment();

        switch (true) {
            case $transaction instanceof Authorization:
                $payment->registerAuthorizationNotification($transaction->getAmount());
                break;
            case $transaction instanceof Charge:
                $payment->registerCaptureNotification($transaction->getAmount());
                break;
            default:
        }

        $order->setCanSendNewEmailFlag(true);
        $order->setState(Order::STATE_PROCESSING);

        $this->_paymentRepository->save($payment);
        $this->_orderRepository->save($order);
        $this->_orderSender->send($order);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }
}
