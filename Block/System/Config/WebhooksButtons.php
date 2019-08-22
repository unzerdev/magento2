<?php

namespace Heidelpay\Gateway2\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Zend_Json;

/**
 * Adminhtml Webhook Configuration Buttons Block
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
class WebhooksButtons extends Field
{
    protected $_template = 'Heidelpay_Gateway2::system/config/webhooks.phtml';

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * @inheritDoc
     */
    public function getRegisterAction(): string
    {
        return $this->getUrl('hpg2/webhooks/register');
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getRegisterButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');
        $button->setData([
            'id' => 'hpg2_webhooks_register',
            'label' => __('Register webhooks'),
            'onclick' => 'location.href = ' . Zend_Json::encode($this->getRegisterAction()),
        ]);

        return $button->toHtml();
    }

    /**
     * @inheritDoc
     */
    public function getUnregisterAction(): string
    {
        return $this->getUrl('hpg2/webhooks/unregister');
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getUnregisterButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');
        $button->setData([
            'id' => 'hpg2_webhooks_unregister',
            'label' => __('Unregister webhooks'),
            'onclick' => 'location.href = ' . Zend_Json::encode($this->getUnregisterAction()),
        ]);

        return $button->toHtml();
    }
}
