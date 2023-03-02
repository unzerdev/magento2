<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Customer Account Order Invoice Information Block
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
 * @package  unzerdev/magento2
 */
class PaylaterInvoiceB2b extends Invoice
{
    protected $_template = 'Unzer_PAPI::info/paylater_invoice_b2b.phtml';

    /**
     * @inheritDoc
     */
    public function toPdf(): string
    {
        $this->setTemplate('Unzer_PAPI::info/pdf/paylater_invoice_b2b.phtml');
        return $this->toHtml();
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function getCustomerSalutation(): string
    {
        return $this->_getPayment()->getCustomer()->getSalutation();
    }

    /**
     * @return string|null
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function getCustomerBirthdate(): ?string
    {
        return $this->_getPayment()->getCustomer()->getBirthDate();
    }
}
