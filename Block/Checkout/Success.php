<?php

namespace Heidelpay\Gateway2\Block\Checkout;

use heidelpayPHP\Resources\Payment;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

/**
 * Onepage Checkout Success Information Block
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
class Success extends \Magento\Checkout\Block\Success
{
    protected $_template = 'Heidelpay_Gateway2::info/invoice.phtml';

    /**
     * @var Session|null
     */
    protected $_checkoutSession = null;

    /**
     * Success constructor.
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $orderFactory, $data);

        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Returns additional payment information.
     *
     * @return string
     */
    public function getAdditionalPaymentInformation(): string
    {
        /** @var Order $order */
        $order = $this->_checkoutSession->getLastRealOrder();

        return $order
            ->getPayment()
            ->getMethodInstance()
            ->getAdditionalPaymentInformation($order);
    }
}
