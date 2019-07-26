<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Heidelpay\Gateway2\Helper\Order as OrderHelper;
use Heidelpay\Gateway2\Model\PaymentInformation;
use Heidelpay\Gateway2\Model\PaymentInformationFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order;

abstract class AbstractPaymentAction extends Action
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var OrderHelper
     */
    protected $_orderHelper;

    /**
     * @var PaymentInformationFactory
     */
    protected $_paymentInformationFactory;

    /**
     * AbstractPaymentAction constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderHelper $_orderHelper
     * @param PaymentInformationFactory $paymentInformationFactory
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderHelper $_orderHelper,
        PaymentInformationFactory $paymentInformationFactory
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderHelper = $_orderHelper;
        $this->_paymentInformationFactory = $paymentInformationFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        /** @var Order $order */
        $order = $this->_checkoutSession->getLastRealOrder();

        /** @var HttpInterface $response */
        $response = $this->getResponse();

        if ($order === null || $order->getId() === null) {
            $response->setHttpResponseCode(400);
            return $response;
        }

        /** @var PaymentInformation $paymentInformation */
        $paymentInformation = $this->_paymentInformationFactory->create();
        $paymentInformation->load($this->_orderHelper->getExternalId($order), 'external_id');

        if ($paymentInformation->getId() === null) {
            $response->setHttpResponseCode(400);
            return $response;
        }

        return $this->executeWith($order, $paymentInformation);
    }

    /**
     * @param Order $order
     * @param PaymentInformation $paymentInformation
     * @return ResultInterface|ResponseInterface
     */
    abstract public function executeWith(Order $order, PaymentInformation $paymentInformation);
}
