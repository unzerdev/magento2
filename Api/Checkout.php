<?php

namespace Unzer\PAPI\Api;

use Exception;
use Unzer\PAPI\Api\Data\Customer;
use Unzer\PAPI\Helper\Order as OrderHelper;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;

/**
 * Checkout API Interface Implementation.
 *
 * Copyright (C) 2021 - today Unzer GmbH
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
 * @link  https://docs.unzer.com/
 *
 * @author Justin Nuß
 *
 * @package  unzerdev/magento2
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
     * Returns the external customer for the current quote.
     *
     * @param string|null $guestEmail E-Mail address used for quote, in case customer is not logged in.
     *
     * @return Customer|null
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getExternalCustomer(?string $guestEmail = null): ?Customer
    {
        /** @var Quote $quote */
        $quote = $this->_checkoutSession->getQuote();

        $email = $guestEmail ?? $quote->getCustomerEmail();

        if ($email === null) {
            return null;
        }

        try {
            $customerResource = $this->_orderHelper->createCustomerFromQuote($quote, $email);
        } catch (Exception $e) {
            $customerResource = null;
        }

        return $customerResource !== null ? Customer::fromResource($customerResource) : null;
    }
}
