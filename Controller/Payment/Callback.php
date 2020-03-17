<?php

namespace Heidelpay\MGW\Controller\Payment;

use Exception;
use Heidelpay\MGW\Helper\Payment as PaymentHelper;
use Heidelpay\MGW\Model\Config;
use heidelpayPHP\Constants\PaymentState;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Model\Order;

/**
 * Callback action called when customers return from a payment provider
 *
 * Copyright (C) 2019 heidelpay GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.heidelpay.com/
 *
 * @author Justin Nuß
 *
 * @package  heidelpay/magento2-merchant-gateway
 */
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
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * Callback constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     * @param Config $moduleConfig
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        Config $moduleConfig,
        PaymentHelper $paymentHelper
    )
    {
        parent::__construct($context, $checkoutSession, $moduleConfig);

        $this->_cartManagement = $cartManagement;
        $this->_messageManager = $messageManager;
        $this->_paymentHelper = $paymentHelper;
    }

    /**
     * @inheritDoc
     */
    public function executeWith(Order $order, Payment $payment)
    {
        switch ($payment->getState()) {
            case PaymentState::STATE_CANCELED:
                return $this->cancelOrder();
            case PaymentState::STATE_COMPLETED:
                $this->_paymentHelper->setOrderState($order, Order::STATE_PROCESSING);
                break;
            case PaymentState::STATE_PENDING:
                $authorization = $payment->getAuthorization();

                if ($authorization !== null) {
                    if ($authorization->isSuccess()) {
                        $this->_paymentHelper->setOrderState(
                            $order,
                            Order::STATE_PROCESSING,
                            PaymentHelper::STATUS_READY_TO_CAPTURE
                        );
                    }
                } elseif ($payment->getPaymentType()->isInvoiceType()) {
                    $charge = $payment->getChargeByIndex(0);

                    if ($charge->isSuccess()) {
                        $this->_paymentHelper->setOrderState($order, Order::STATE_PROCESSING);
                    }
                }
                break;
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('checkout/onepage/success');
        return $redirect;
    }
}
