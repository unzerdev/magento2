<?php

namespace Heidelpay\Gateway2\Controller\Payment;

use Heidelpay\Gateway2\Helper\Order as OrderHelper;
use Heidelpay\Gateway2\Model\PaymentInformation;
use Heidelpay\Gateway2\Model\PaymentInformationFactory;
use Magento\Sales\Model\Order;

class Redirect extends AbstractPaymentAction
{
    /**
     * @var OrderHelper
     */
    protected $_orderHelper;

    /**
     * @var PaymentInformationFactory
     */
    protected $_paymentInformationFactory;

    /**
     * @inheritDoc
     */
    public function executeWith(Order $order, PaymentInformation $paymentInformation)
    {
        /** @var string|null $redirectUrl */
        $redirectUrl = $paymentInformation->getRedirectUrl();

        $paymentInformation->setOrder($order);
        $paymentInformation->setRedirectUrl(null);
        $paymentInformation->save();

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($redirectUrl);
        return $redirect;
    }
}