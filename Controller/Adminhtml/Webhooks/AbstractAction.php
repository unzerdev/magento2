<?php

namespace Unzer\PAPI\Controller\Adminhtml\Webhooks;

use Unzer\PAPI\Helper\Webhooks as WebhooksHelper;
use Unzer\PAPI\Model\Config;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Abstract controller base for webhook processing.
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
abstract class AbstractAction extends Action
{
    public const URL_PARAM_STORE = 'store';

    /**
     * @var Config Config
     */
    protected $_moduleConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var WebhooksHelper
     */
    protected $_webhooksHelper;

    /**
     * Register constructor.
     * @param Action\Context $context
     * @param Config $moduleConfig
     * @param StoreManagerInterface $storeManager
     * @param WebhooksHelper $webhooksHelper
     */
    public function __construct(
        Action\Context $context,
        Config $moduleConfig,
        StoreManagerInterface $storeManager,
        WebhooksHelper $webhooksHelper
    )
    {
        parent::__construct($context);

        $this->_moduleConfig = $moduleConfig;
        $this->_storeManager = $storeManager;
        $this->_webhooksHelper = $webhooksHelper;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getWebhookUrl(): string
    {
        /** @var int|string $storeIdentifier */
        $storeIdentifier = $this->getRequest()->getParam(self::URL_PARAM_STORE);

        $store = $this->_storeManager->getStore($storeIdentifier);

        return $this->_webhooksHelper->getUrl($store);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode()
    {
        $storeId = $this->getRequest()->getParam(self::URL_PARAM_STORE);
        return $this->_storeManager->getStore($storeId)->getCode();
    }
}
