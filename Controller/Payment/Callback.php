<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Exception;
use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

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
     * Callback constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     * @param Config $moduleConfig
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        Config $moduleConfig,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender
    ) {
        parent::__construct($context, $checkoutSession, $moduleConfig);
        $this->_cartManagement = $cartManagement;
        $this->_messageManager = $messageManager;
        $this->_moduleConfig = $moduleConfig;
        $this->_orderRepository = $orderRepository;
        $this->_orderSender = $orderSender;
    }

    /**
     * @inheritDoc
     * @throws HeidelpayApiException
     */
    public function executeWith(Order $order, Payment $payment)
    {
        if ($payment->isCompleted()) {
            $response = $this->handleSuccess($order);
        } elseif ($payment->isPending()) {
            // Some methods report cancelled charges as pending, so we must manually check the transaction state.
            $charge = $payment->getChargeByIndex(0);

            if ($charge === null || $charge->isSuccess()) {
                $response = $this->handleSuccess($order);
            } else {
                $response = $this->handleError($order, $payment);
            }
        } else {
            $response = $this->handleError($order, $payment);
        }

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
        $order->setCanSendNewEmailFlag(true);
        $order->setState(Order::STATE_PROCESSING);

        $this->_orderRepository->save($order);
        $this->_orderSender->send($order);

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }
}
