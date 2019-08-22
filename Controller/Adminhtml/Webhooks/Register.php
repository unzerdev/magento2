<?php

namespace Heidelpay\Gateway2\Controller\Adminhtml\Webhooks;

use Heidelpay\Gateway2\Helper\Webhooks as WebhooksHelper;
use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Webhook;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Controller for registering webhooks via the backend
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
class Register extends Action
{
    /**
     * @var Config Config
     */
    protected $_moduleConfig;

    /**
     * @var WebhooksHelper
     */
    protected $_webhooksHelper;

    /**
     * Register constructor.
     * @param Action\Context $context
     * @param Config $moduleConfig
     * @param WebhooksHelper $webhooksHelper
     */
    public function __construct(Action\Context $context, Config $moduleConfig, WebhooksHelper $webhooksHelper)
    {
        parent::__construct($context);

        $this->_moduleConfig = $moduleConfig;
        $this->_webhooksHelper = $webhooksHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(): Redirect
    {
        /** @var string $webhookUrl */
        $webhookUrl = $this->_webhooksHelper->getUrl();

        try {
            $client = $this->_moduleConfig->getHeidelpayClient();

            $isRegistered = false;

            foreach ($client->fetchAllWebhooks() as $webhook) {
                /** @var Webhook $webhook */
                if ($webhook->getUrl() === $webhookUrl) {
                    $isRegistered = true;
                    break;
                }
            }

            if (!$isRegistered) {
                $client->createWebhook($webhookUrl, 'all');
            }

            $this->messageManager->addSuccess(__('Successfully registered webhooks'));
        } catch (HeidelpayApiException $e) {
            $this->messageManager->addError(__($e->getMerchantMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($this->getRequest()->getHeader('Referer'));
        return $redirect;
    }
}
