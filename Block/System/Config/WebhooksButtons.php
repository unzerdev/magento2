<?php

namespace Heidelpay\MGW\Block\System\Config;

use Heidelpay\MGW\Controller\Adminhtml\Webhooks\AbstractAction;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
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
    protected $_template = 'Unzer_PAPI::system/config/webhooks.phtml';

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * WebhooksButtons constructor.
     * @param Context $context
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->_request = $request;
        $this->_storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRegisterAction(): string
    {
        return $this->getUrl('hpmgw/webhooks/register', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
        ]);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getRegisterButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');
        $button->setData([
            'id' => 'hpmgw_webhooks_register',
            'label' => __('Register webhooks'),
            'onclick' => 'location.href = ' . Zend_Json::encode($this->getRegisterAction()),
        ]);

        return $button->toHtml();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getUnregisterAction(): string
    {
        return $this->getUrl('hpmgw/webhooks/unregister', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
        ]);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getUnregisterButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');
        $button->setData([
            'id' => 'hpmgw_webhooks_unregister',
            'label' => __('Unregister webhooks'),
            'onclick' => 'location.href = ' . Zend_Json::encode($this->getUnregisterAction()),
        ]);

        return $button->toHtml();
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    protected function getStoreIdentifier(): int
    {
        /** @var int|string $storeIdentifier */
        $storeIdentifier = $this->getRequest()->getParam(AbstractAction::URL_PARAM_STORE);

        $store = $this->_storeManager->getStore($storeIdentifier);

        return $store->getId();
    }
}
