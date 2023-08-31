<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Checkout\Success;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Unzer\PAPI\Model\Method\Base;

/**
 * Onepage Checkout Success Payment Information Block
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
class AdditionalPaymentInformation extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::success/additional_payment_information.phtml';

    /**
     * @var Session|null
     */
    protected ?Session $_checkoutSession = null;

    /**
     * AdditionalPaymentInformation constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param array $data
     */
    public function __construct(Context $context, Session $checkoutSession, array $data = [])
    {
        parent::__construct($context, $data);

        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Returns additional payment information for the customer.
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getAdditionalPaymentInformation(): ?string
    {
        $order = $this->_checkoutSession->getLastRealOrder();

        $methodInstance = $order
            ->getPayment()
            ->getMethodInstance();

        if (!$methodInstance instanceof Base) {
            return null;
        }

        return $methodInstance->getAdditionalPaymentInformation($order);
    }
}
