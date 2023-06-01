<?php

namespace Unzer\PAPI\Model\Method;

/**
 * Apple Pay payment method
 *
 * Copyright (C) 2023 - today Unzer GmbH
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
class Applepay extends Base
{
    /**
     * @inheritDoc
     */
    public function getFrontendConfig(): array
    {
        //@todo serialize?
        $supportedNetworks = $this->_scopeConfig->getValue('payment/unzer/applepay/supported_networks');
        $supportedNetworks = explode(',',$supportedNetworks);
        $supportedNetworks = $supportedNetworks;

        return [
            'supportedNetworks' => $supportedNetworks,
            'merchantCapabilities' => ['supports3DS'],
            'label' => $this->_scopeConfig->getValue('payment/unzer_applepay/display_name') //label
        ];
    }

    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return false;
    }
}
