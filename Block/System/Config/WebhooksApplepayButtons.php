<?php

namespace Unzer\PAPI\Block\System\Config;

use Unzer\PAPI\Controller\Adminhtml\Webhooks\AbstractAction;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use UnzerSDK\Unzer;
use Zend_Json;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Adminhtml Webhook Configuration Buttons Block
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
 *
 * @package  unzerdev/magento2
 */
class WebhooksApplepayButtons extends Field
{
    protected $_template = 'Unzer_PAPI::system/config/webhooksapplepay.phtml';

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var bool
     */
    protected bool $_this_certificateIsActive = false;
    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * WebhooksButtons constructor.
     * @param Context $context
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param array $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        Curl $curl,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->curl = $curl;
        $this->_this_certificateIsActive = $this->isCertificateActive();
    }

    /**
     * @return bool
     */
    public function isCertificateActive(){
        $unzerPrivateKey = base64_encode($this->_scopeConfig->getValue('payment/unzer/private_key') . ":");
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("Authorization", "Basic " . $unzerPrivateKey);

        $certificateId = $this->_scopeConfig->getValue("payment/unzer_applepay/csr_certificate_response");
        $url = $this->getApiUrl($this->_scopeConfig->getValue('payment/unzer/logging'), 'v1/keypair/applepay/certificates/' . $certificateId);

        // get method
        $this->curl->get($url);

        // output of curl requestt
        $result = $this->curl->getBody();

        $result = (array)json_decode($result);

        if(array_key_exists('active',$result) && $result['active'] == 1){
            $this->_this_certificateIsActive = true;
        }
        return $this->_this_certificateIsActive;
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
    public function getRegisterPrivatekeyAction(): string
    {
        return $this->getUrl('unzer/webhooks/registerapplepay', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
            'switch' => 'registerPrivateKey'
        ]);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getRegisterPrivatekeyButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');
        $button->setData([
            'id' => 'unzer_webhooks_applepay_privatekey',
            'label' => __('Register privatekey'),
            'onclick' => 'location.href = ' . Zend_Json::encode($this->getRegisterPrivatekeyAction())
        ]);

        return $button->toHtml();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRegisterCertificatesAction(): string
    {
        return $this->getUrl('unzer/webhooks/registerapplepay', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
            'switch' => 'registerCertificate'
        ]);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getRegisterCertificatesButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');
        $button->setData([
            'id' => 'unzer_webhooks_applepay_certificates',
            'label' => __('Register Certificate'),
            'onclick' => 'location.href = ' . Zend_Json::encode($this->getRegisterCertificatesAction()),
        ]);

        return $button->toHtml();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getActivateApplepayAction(): string
    {
        return $this->getUrl('unzer/webhooks/registerapplepay', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
            'switch' => 'activate'
        ]);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getActivateApplepayButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');

        if($this->_this_certificateIsActive){
            $button->setData([
                'id' => 'unzer_webhooks_applepay_activate',
                'label' => __('Active'),
                'onclick' => 'location.href = ' . Zend_Json::encode($this->getActivateApplepayAction()),
                'style' => 'background-color: #ebf5d6;'
            ]);
        } else{
            $button->setData([
                'id' => 'unzer_webhooks_applepay_activate',
                'label' => __('Activate'),
                'onclick' => 'location.href = ' . Zend_Json::encode($this->getActivateApplepayAction()),
            ]);
        }
        return $button->toHtml();
    }

    public function getApiUrl($logging, $url)
    {
        $envPrefix = 'sbx-';
        if (!$logging) {
            $envPrefix = '';
        }
        return "https://" . $envPrefix . Unzer::BASE_URL . "/" . $url;
    }
}
