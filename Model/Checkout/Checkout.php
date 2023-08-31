<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Checkout;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Unzer\PAPI\Api\CheckoutInterface;
use Unzer\PAPI\Api\Data\CustomerInterface;
use Unzer\PAPI\Api\Data\CustomerInterfaceFactory;
use Unzer\PAPI\Helper\Order as OrderHelper;
use Unzer\PAPI\Model\Checkout\Data\Customer;

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
 */
class Checkout implements CheckoutInterface
{
    /**
     * @var Session
     */
    protected Session $_checkoutSession;

    /**
     * @var OrderHelper
     */
    protected OrderHelper $_orderHelper;

    /**
     * @var CustomerInterfaceFactory
     */
    private CustomerInterfaceFactory $customerInterfaceFactory;

    /**
     * Checkout constructor.
     *
     * @param Session $checkoutSession
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Session $checkoutSession,
        OrderHelper $orderHelper,
        CustomerInterfaceFactory $customerInterfaceFactory
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderHelper = $orderHelper;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
    }

    /**
     * Returns the external customer for the current quote.
     *
     * @param string|null $guestEmail E-Mail address used for quote, in case customer is not logged in.
     *
     * @return Customer|null
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
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

        if ($customerResource === null) {
            return null;
        }

        return $this->customerInterfaceFactory->create()->fromResource($customerResource);
    }
}
