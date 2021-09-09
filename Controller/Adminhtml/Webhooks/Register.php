<?php

namespace Unzer\PAPI\Controller\Adminhtml\Webhooks;

use Exception;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Webhook;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Controller for registering webhooks via the backend
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
class Register extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function execute(): Redirect
    {
        try {
            $webhookUrl = $this->getWebhookUrl();

            $client = $this->_moduleConfig->getUnzerClient($this->getStoreCode());

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

            $this->messageManager->addSuccessMessage(__('Successfully registered webhooks'));
        } catch (UnzerApiException $e) {
            $this->messageManager->addErrorMessage(__($e->getMerchantMessage()));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($this->getRequest()->getHeader('Referer'));
        return $redirect;
    }
}
