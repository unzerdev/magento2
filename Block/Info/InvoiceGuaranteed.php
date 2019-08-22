<?php

namespace Heidelpay\MGW\Block\Info;

/**
 * Customer Account Order Invoice Information Block
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
class InvoiceGuaranteed extends Invoice
{
    protected $_template = 'Heidelpay_MGW::info/invoice_guaranteed.phtml';

    /**
     * @inheritDoc
     */
    public function toPdf(): string
    {
        $this->setTemplate('Heidelpay_MGW::info/pdf/invoice_guaranteed.phtml');
        return $this->toHtml();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    public function getCustomerSalutation(): string
    {
        return $this->_getPayment()->getCustomer()->getSalutation();
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    public function getCustomerBirthdate(): string
    {
        return $this->_getPayment()->getCustomer()->getBirthDate();
    }
}
