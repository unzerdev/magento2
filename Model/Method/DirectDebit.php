<?php

namespace Heidelpay\MGW\Model\Method;

/**
 * Direct debit payment method
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
class DirectDebit extends Base
{
    const CONFIG_PATH_STORE_NAME = 'general/store_information/name';

    /**
     * @inheritDoc
     */
    public function getFrontendConfig(): array
    {
        $merchantName = $this->getConfigData('merchant_name');

        if (empty($merchantName)) {
            $merchantName = $this->_scopeConfig->getValue(self::CONFIG_PATH_STORE_NAME);

            if (empty($merchantName)) {
                $merchantName = __('the merchant');
            }
        }

        return [
            'merchantName' => $merchantName,
        ];
    }
}
