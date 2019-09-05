<?php

namespace Heidelpay\MGW\Controller\Payment;

use Heidelpay\MGW\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\StatusResolver;

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
     * @var StatusResolver
     */
    protected $_orderStatusResolver;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $_paymentRepository;

    /**
     * @var OrderPayment\Transaction\Repository
     */
    protected $_transactionRepository;

    /**
     * Callback constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     * @param Config $moduleConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param StatusResolver $orderStatusResolver
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param OrderPayment\Transaction\Repository $transactionRepository
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        Config $moduleConfig,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        StatusResolver $orderStatusResolver,
        OrderPaymentRepositoryInterface $paymentRepository,
        OrderPayment\Transaction\Repository $transactionRepository
    )
    {
        parent::__construct($context, $checkoutSession, $moduleConfig);

        $this->_cartManagement = $cartManagement;
        $this->_messageManager = $messageManager;
        $this->_moduleConfig = $moduleConfig;
        $this->_orderRepository = $orderRepository;
        $this->_orderSender = $orderSender;
        $this->_orderStatusResolver = $orderStatusResolver;
        $this->_paymentRepository = $paymentRepository;
        $this->_transactionRepository = $transactionRepository;
    }

    /**
     * @inheritDoc
     * @throws HeidelpayApiException
     */
    public function executeWith(Order $order, Payment $payment)
    {
        /** @var Authorization|Charge|AbstractHeidelpayResource $transaction */
        $transaction = $payment->getAuthorization() ?? $payment->getChargeByIndex(0);

        if ($payment->isCompleted()) {
            $response = $this->handleSuccess($order, $transaction);
        } elseif ($payment->isPending()) {
            $response = $this->handlePending($order, $transaction);
        } else {
            $response = $this->handleError($order, $transaction);
        }

        return $response;
    }

    /**
     * @param Order $order
     * @param Authorization|Charge|AbstractHeidelpayResource $transaction
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handleError(
        Order $order,
        AbstractHeidelpayResource $transaction
    ): \Magento\Framework\Controller\Result\Redirect
    {
        return $this->handleErrorMessage($order, $transaction->getMessage()->getCustomer());
    }

    /**
     * @param Order $order
     * @param string $message
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function handleErrorMessage(Order $order, string $message): \Magento\Framework\Controller\Result\Redirect
    {
        $this->_checkoutSession->restoreQuote();
        $order->cancel();
        $this->_orderRepository->save($order);

        $this->_messageManager->addError($message);

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
        $order->setState(Order::STATE_PAYMENT_REVIEW);
        $order->setStatus($this->_orderStatusResolver->getOrderStatusByState($order, $order->getState()));

        $this->_orderRepository->save($order);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }

    /**
     * @param Order $order
     * @param Authorization|Charge|AbstractHeidelpayResource $transaction
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handleSuccess(
        Order $order,
        AbstractHeidelpayResource $transaction
    ): \Magento\Framework\Controller\Result\Redirect
    {
        /** @var OrderPayment $payment */
        $payment = $order->getPayment();

        try {
            /** @var OrderPayment\Transaction $paymentTransaction */
            $paymentTransaction = $this->_transactionRepository->getByTransactionId(
                $transaction->getUniqueId(),
                $payment->getId(),
                $order->getId()
            );
        } catch (\Exception $e) {
            return $this->handleErrorMessage($order, $e->getMessage());
        }

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
        $order->setStatus($this->_orderStatusResolver->getOrderStatusByState($order, $order->getState()));

        $paymentTransaction->setIsClosed(true);

        $this->_transactionRepository->save($paymentTransaction);
        $this->_paymentRepository->save($payment);
        $this->_orderRepository->save($order);
        $this->_orderSender->send($order);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }
}
