<?php
namespace Unzer\PAPI\Controller\Applepay;

use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Context;

/**
 * Apple Pay Merchant Validation Controller
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
 */
class MerchantValidation extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig

    ) {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        header("Content-type: application/json; charset=utf-8");

        //POST from applepay.js jQuery.post()
        $jsonData = json_decode(file_get_contents('php://input'), true);
        $postValidationUrl = $jsonData['merchantValidationUrl'];

        $merchantIdentifier = $this->_scopeConfig->getValue('payment/unzer_applepay/apple_pay_merchant_id');
        $displayName = $this->_scopeConfig->getValue('payment/unzer_applepay/display_name');
        $domainName = $this->_scopeConfig->getValue('payment/unzer_applepay/domain_name');

        //@todo need Refactoring
        $sslCert = '../app/etc/upload/applepay/'.$this->_scopeConfig->getValue('payment/unzer/applepay/certificate_file');
        $sslKey = '../app/etc/upload/applepay/'.$this->_scopeConfig->getValue('payment/unzer/applepay/private_key_file');

        $applepaySession = new ApplepaySession($merchantIdentifier, $displayName, $domainName);

        $appleAdapter = new ApplepayAdapter();
        $appleAdapter->init($sslCert, $sslKey);

        try {
            $validationUrl = $postValidationUrl;
            $validationResponse = $appleAdapter->validateApplePayMerchant(
                $validationUrl,
                $applepaySession
            );
            echo $validationResponse;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        exit();
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
