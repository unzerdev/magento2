<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Heidelpay\Gateway2\Helper\Order as OrderHelper;
use Heidelpay\Gateway2\Model\Config;
use Heidelpay\Gateway2\Model\PaymentInformation;
use Heidelpay\Gateway2\Model\PaymentInformationFactory;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Traits\HasStates;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

abstract class AbstractCallback extends AbstractPaymentAction
{
    /**
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderHelper $orderHelper,
        PaymentInformationFactory $paymentInformationFactory,
        Config $moduleConfig,
        OrderRepositoryInterface $orderRepository
    )
    {
        parent::__construct($context, $checkoutSession, $orderHelper, $paymentInformationFactory);
        $this->_moduleConfig = $moduleConfig;
        $this->_orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     * @throws HeidelpayApiException
     */
    public function executeWith(Order $order, PaymentInformation $paymentInformation)
    {
        /** @var Payment $payment */
        $payment = $this->_moduleConfig
            ->getHeidelpayClient()
            ->fetchPayment($paymentInformation->getPaymentId());

        /** @var HasStates $result */
        $result = $this->getStateForPayment($order, $payment);

        if ($result->isError()) {
            $response = $this->handleError($order);
        } elseif ($result->isPending()) {
            $response = $this->getResponse();
            $response->setHttpResponseCode(400);
        } else {
            $response = $this->handleSuccess($order);
        }

        $paymentInformation->setRedirectUrl(null);
        $paymentInformation->save();

        return $response;
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
        $this->_checkoutSession->restoreQuote();
        $order->cancel();
        $this->_orderRepository->save($order);

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
        $this->_orderRepository->save($order);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }
}