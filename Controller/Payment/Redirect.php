<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Heidelpay\Gateway2\Model\Config;
use Heidelpay\Gateway2\Model\Method\Base;
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
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;

class Redirect extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(Context $context, Session $checkoutSession)
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
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

        /** @var OrderPaymentInterface $payment */
        $payment = $order->getPayment();

        /** @var array $paymentData */
        $paymentData = $payment->getAdditionalInformation();

        $redirect = $this->resultRedirectFactory->create();

        if (isset($paymentData[Base::KEY_REDIRECT_URL])) {
            $redirect->setUrl($paymentData[Base::KEY_REDIRECT_URL]);
        } else {
            $redirect->setPath('checkout/onepage/success');
        }

        return $redirect;
    }
}