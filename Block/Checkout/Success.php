<?php

namespace Heidelpay\Gateway2\Block\Checkout;

use heidelpayPHP\Resources\Payment;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;


class Success extends \Magento\Checkout\Block\Success
{
    protected $_template = 'Heidelpay_Gateway2::info/invoice.phtml';

    /**
     * @var Session|null
     */
    protected $_checkoutSession = null;

    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        array $data = []
    )
    {
        parent::__construct($context, $orderFactory, $data);

        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Returns additional payment information.
     *
     * @return Payment
     */
    public function getAdditionalPaymentInformation()
    {
        /** @var Order $order */
        $order = $this->_checkoutSession->getLastRealOrder();

        return $order
            ->getPayment()
            ->getMethodInstance()
            ->getAdditionalPaymentInformation($order);
    }
}