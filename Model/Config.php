<?php

namespace Unzer\PAPI\Model;

use Unzer\PAPI\Model\Logger\DebugHandler;
use UnzerSDK\Unzer;
use UnzerSDK\Interfaces\DebugHandlerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\ScopeInterface;

/**
 * Global Module configuration and SDK provider
 *
 * Copyright (C) 2021 Unzer GmbH
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
class Config extends \Magento\Payment\Gateway\Config\Config
{
    const BASE_CONFIGURATION_PATH = 'payment/unzer/';

    const KEY_PUBLIC_KEY = 'public_key';
    const KEY_PRIVATE_KEY = 'private_key';
    const KEY_LOGGING = 'logging';

    const METHOD_BASE = 'unzer';
    const METHOD_CARDS = 'unzer_cards';
    const METHOD_DIRECT_DEBIT = 'unzer_direct_debit';
    const METHOD_DIRECT_DEBIT_GUARANTEED = 'unzer_direct_debit_guaranteed';
    const METHOD_FLEXIPAY_DIRECT = 'unzer_flexipay_direct';
    const METHOD_IDEAL = 'unzer_ideal';
    const METHOD_INVOICE = 'unzer_invoice';
    const METHOD_INVOICE_GUARANTEED_B2B = 'unzer_invoice_guaranteed_b2b';
    const METHOD_INVOICE_GUARANTEED = 'unzer_invoice_guaranteed';
    const METHOD_PAYPAL = 'unzer_paypal';
    const METHOD_SOFORT = 'unzer_sofort';

    /**
     * @var DebugHandlerInterface
     */
    private $_debugHandler;

    /**
     * @var Resolver
     */
    private $_localeResolver;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * Config constructor.
     * @param Resolver $localeResolver
     * @param ScopeConfigInterface $scopeConfig
     * @param DebugHandler $debugHandler
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        Resolver $localeResolver,
        ScopeConfigInterface $scopeConfig,
        DebugHandler $debugHandler,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);

        $this->_debugHandler = $debugHandler;
        $this->_localeResolver = $localeResolver;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @param string|null $storeId
     * @return bool
     */
    private function isDebugMode(string $storeId = null): bool
    {
        return $this->_scopeConfig->isSetFlag(
            self::BASE_CONFIGURATION_PATH . self::KEY_LOGGING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the public key.
     *
     * @param string|null $storeId
     * @return string
     */
    public function getPublicKey(string $storeId = null): string
    {
        return $this->_scopeConfig->getValue(
            self::BASE_CONFIGURATION_PATH . self::KEY_PUBLIC_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the private key.
     *
     * @param string|null $storeId
     * @return string
     */
    public function getPrivateKey(string $storeId = null): string
    {
        return $this->_scopeConfig->getValue(
            self::BASE_CONFIGURATION_PATH . self::KEY_PRIVATE_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns an API client using the configured private key.
     *
     * @param string|null $storeId
     * @return Unzer
     */
    public function getUnzerClient(string $storeId = null): Unzer
    {
        $client = new Unzer(
            $this->getPrivateKey($storeId),
            $this->_localeResolver->getLocale()
        );

        $client->setDebugMode($this->isDebugMode($storeId));
        $client->setDebugHandler($this->_debugHandler);

        return $client;
    }
}
