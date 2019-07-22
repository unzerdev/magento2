<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order;

class Redirect extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var Config
     */
    private $moduleConfig;

    public function __construct(Context $context, Session $checkoutSession, Config $moduleConfig)
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->moduleConfig = $moduleConfig;
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
        /** @var Order $lastOrder */
        $lastOrder = $this->checkoutSession->getLastRealOrder();

        /** @var HttpInterface $response */
        $response = $this->getResponse();

        if ($lastOrder === null || $lastOrder->getId() === null) {
            $response->setHttpResponseCode(400);
            return $response;
        }

        $client = new Heidelpay($this->moduleConfig->getPrivateKey());

        /** @var Payment $payment */
        $payment = $client->fetchPaymentByOrderId($lastOrder->getIncrementId());

        /** @var Authorization $authorization */
        $authorization = $payment->getAuthorization();

        $redirect = $this->resultRedirectFactory->create();

        if ($authorization->isPending()) {
            $redirect->setUrl($authorization->getRedirectUrl());
        } else if ($authorization->isSuccess()) {
            $redirect->setPath('checkout/onepage/success');
        } else {
            $response->setHttpResponseCode(400);
            return $response;
        }

        return $redirect;
    }
}