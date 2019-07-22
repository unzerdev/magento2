<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Traits\HasStates;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

abstract class AbstractCallback extends Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $moduleConfig;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $moduleConfig,
        OrderRepositoryInterface $orderRepository
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->moduleConfig = $moduleConfig;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return ResultInterface|ResponseInterface
     * @throws HeidelpayApiException
     */
    public function execute()
    {
        /** @var Order $order */
        $order = $this->checkoutSession->getLastRealOrder();

        /** @var HttpInterface $response */
        $response = $this->getResponse();

        if ($order === null || $order->getId() === null) {
            $response->setHttpResponseCode(400);
            return $response;
        }

        /** @var Heidelpay $client */
        $client = $this->moduleConfig->getHeidelpayClient();

        /** @var Payment $payment */
        $payment = $client->fetchPaymentByOrderId($order->getIncrementId());

        /** @var HasStates $result */
        $result = $this->getStateForPayment($order, $payment);

        if ($result->isError()) {
            return $this->handleError($order);
        } elseif ($result->isSuccess()) {
            return $this->handleSuccess($order);
        } else {
            $response->setHttpResponseCode(400);
            return $response;
        }
    }

    /**
     * @param Order $order
     * @return HasStates
     * @throws HeidelpayApiException
     */
    abstract protected function getStateForPayment(Order $order, Payment $payment);

    /**
     * @param Order $order
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handleError(Order $order)
    {
        $this->checkoutSession->restoreQuote();
        $order->cancel();
        $this->orderRepository->save($order);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/cart');
        return $redirect;
    }

    /**
     * @param Order $order
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handleSuccess(Order $order)
    {
        $order->setState(Order::STATE_PROCESSING);
        $this->orderRepository->save($order);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }
}