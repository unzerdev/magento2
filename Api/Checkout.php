<?php

namespace Heidelpay\MGW\Api;

use Exception;
use Heidelpay\MGW\Helper\Order as OrderHelper;
use heidelpayPHP\Resources\Customer;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;

/**
 * Checkout API Interface Implementation.
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
class Checkout implements CheckoutInterface
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
     * Checkout constructor.
     * @param Session $checkoutSession
     * @param OrderHelper $orderHelper
     */
    public function __construct(Session $checkoutSession, OrderHelper $orderHelper)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderHelper = $orderHelper;
    }

    /**
     * Returns the external customer ID for the current quote.
     *
     * @param string|null $guestEmail E-Mail address used for quote, in case customer is not logged in.
     *
     * @return string|null
     */
    public function getExternalCustomerId(?string $guestEmail = null): ?string
    {
        /** @var Quote $quote */
        $quote = $this->_checkoutSession->getQuote();

        $guestEmail = $guestEmail ?? $quote->getCustomerEmail();

        if ($guestEmail === null) {
            return null;
        }

        /** @var Customer $customer */

        try {
            $customer = $this->_orderHelper->createOrUpdateCustomerFromQuote($quote, $guestEmail);
        } catch (Exception $e) {
            return null;
        }

        return $customer !== null ? $customer->getId() : null;
    }
}
