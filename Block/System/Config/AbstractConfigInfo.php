<?php

namespace Unzer\PAPI\Block\System\Config;

use Magento\Framework\Module\ResourceInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adminhtml Abstract Info Block
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
 * @author David Owusu
 *
 * @package  unzerdev/magento2
 */
class AbstractConfigInfo extends Field
{
    protected $_template = '';

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var ResourceInterface
     */
    protected $moduleResource;

    /**
     * WebhooksButtons constructor.
     *
     * @param Context               $context
     * @param RequestInterface      $request
     * @param StoreManagerInterface $storeManager
     * @param ResourceInterface     $moduleResource
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        ResourceInterface    $moduleResource,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    public function getInfo(): string
    {
        return '';
    }

    protected function _renderScopeLabel(AbstractElement $element)
    {
        return '';
    }
}
