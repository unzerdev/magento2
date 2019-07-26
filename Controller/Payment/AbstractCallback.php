<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Heidelpay\Gateway2\Helper\Order as OrderHelper;
use Heidelpay\Gateway2\Model\Config;
use Heidelpay\Gateway2\Model\PaymentInformation;
use Heidelpay\Gateway2\Model\PaymentInformationFactory;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\EmbeddedResources\Message;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\AbstractTransactionType;
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

        /** @var AbstractTransactionType $transaction */
        $transaction = $this->getTransaction($order, $payment);

        if ($transaction->isSuccess()) {
            $response = $this->handleSuccess($order);
        } elseif ($transaction->isPending()) {
            $response = $this->getResponse();
            $response->setHttpResponseCode(400);
        } else {
            $response = $this->handleError($order, $transaction->getMessage());
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
    abstract protected function getTransaction(Order $order, Payment $payment);

    /**
     * @param Order $order
     * @param Message|null $message
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handleError(Order $order, Message $message = null)
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