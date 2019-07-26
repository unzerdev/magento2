<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Exception;
use Heidelpay\Gateway2\Helper\Order as OrderHelper;
use Heidelpay\Gateway2\Model\Config;
use Heidelpay\Gateway2\Model\PaymentInformation;
use Heidelpay\Gateway2\Model\PaymentInformationFactory;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Authorization;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class Callback extends AbstractPaymentAction
{
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

    public function __construct(
        Context $context,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        OrderHelper $orderHelper,
        PaymentInformationFactory $paymentInformationFactory,
        Config $moduleConfig,
        OrderRepositoryInterface $orderRepository
    )
    {
        parent::__construct($context, $checkoutSession, $orderHelper, $paymentInformationFactory);
        $this->_messageManager = $messageManager;
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

        if ($payment->isPending() || $payment->isCompleted()) {
            $response = $this->handleSuccess($order);
        } else {
            $response = $this->handleError($order, $payment);
        }

        $paymentInformation->setRedirectUrl(null);
        $paymentInformation->save();

        return $response;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function handleError(Order $order, Payment $payment)
    {
        $this->_checkoutSession->restoreQuote();
        $order->cancel();
        $this->_orderRepository->save($order);

        try {
            $transaction = $payment->getAuthorization();
            if (!$transaction instanceof Authorization) {
                $transaction = $payment->getChargeByIndex(0);
            }
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