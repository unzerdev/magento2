<?php

/**
 * Authorize Command for payments
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
 * @author neusta SD GmbH
 *
 * @package  unzerdev/magento2
 */

namespace Unzer\PAPI\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template\Context;
use Unzer\PAPI\Model\Config;

class Currency implements OptionSourceInterface
{

    /**
     * @var Context
     */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Returns current base currency code
     *
     * @return string
     * @throws LocalizedException
     */
    protected function getBaseCurrencyCode(): string
    {
        $requestParams = $this->context->getRequest()->getParams();
        $storeManager = $this->context->getStoreManager();
        if (isset($requestParams['website'])) {
            return $storeManager->getWebsite($requestParams['website'])->getBaseCurrencyCode();
        }

        if (isset($requestParams['store'])) {
            return $storeManager->getStore($requestParams['store'])->getBaseCurrencyCode();
        }

        // storeId 0 = Default Config
        return $storeManager->getStore(0)->getBaseCurrencyCode();
    }

    /**
     * Return currency options
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Config::CURRENCY_BASE,
                'label' => __('Base Currency').' ('.$this->getBaseCurrencyCode().')'
            ],
            [
                'value' => Config::CURRENCY_CUSTOMER,
                'label' => __('Customer Currency')
            ]
        ];
    }
}
