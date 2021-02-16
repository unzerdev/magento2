<?php

namespace Heidelpay\MGW\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url;
use Magento\Store\Api\Data\StoreInterface;
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
    public const URL_PARAM_STORE = 'store';

    /**
     * @var Url
     */
    protected $_urlBuilder;

    /**
     * Webhooks constructor.
     * @param Url $urlBuilder
     */
    public function __construct(Url $urlBuilder)
    {
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * @param StoreInterface|null $store
     * @return string
     */
    public function getUrl(?StoreInterface $store): string
    {
        return $this->_urlBuilder
            ->setScope($store)
            ->getUrl('hpmgw/webhooks/process', ['_nosid' => true, self::URL_PARAM_STORE => $store->getId()]);
    }
}
