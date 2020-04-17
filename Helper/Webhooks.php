<?php

namespace Heidelpay\MGW\Helper;

use Magento\Framework\Url;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Helper for webhook handling
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
class Webhooks
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Url
     */
    protected $_urlBuilder;

    /**
     * Webhooks constructor.
     * @param StoreManagerInterface $storeManager
     * @param Url $urlBuilder
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Url $urlBuilder
    ) {
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        /** @var Store $store */
        $store = $this->_storeManager->getDefaultStoreView();

        return $this->_urlBuilder
            ->setScope($store)
            ->getUrl('hpmgw/webhooks/process', ['_nosid' => true]);
    }
}
