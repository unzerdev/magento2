<?php

namespace Heidelpay\Gateway2\Model;

use Heidelpay\Gateway2\Model\Logger\DebugHandler;
use heidelpayPHP\Heidelpay;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const BASE_CONFIGURATION_PATH = 'payment/hpg2/';

    const KEY_PUBLIC_KEY = 'public_key';
    const KEY_PRIVATE_KEY = 'private_key';
    const KEY_WEBHOOKS_SOURCE_IPS = 'webhooks_source_ips';
    const KEY_LOGGING = 'logging';

    const METHOD_BASE = 'hpg2';
    const METHOD_CREDITCARD = 'hpg2_creditcard';
    const METHOD_DIRECT_DEBIT = 'hpg2_direct_debit';
    const METHOD_DIRECT_DEBIT_GUARANTEED = 'hpg2_direct_debit_guaranteed';
    const METHOD_FLEXIPAY_DIRECT = 'hpg2_flexipay_direct';
    const METHOD_IDEAL = 'hpg2_ideal';
    const METHOD_INVOICE = 'hpg2_invoice';
    const METHOD_INVOICE_GUARANTEED = 'hpg2_invoice_guaranteed';
    const METHOD_PAYPAL = 'hpg2_paypal';
    const METHOD_SOFORT = 'hpg2_sofort';

    /**
     * @var DebugHandler
     */
    private $_debugHandler;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param DebugHandler $debugHandler
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        DebugHandler $debugHandler,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);

        $this->_debugHandler = $debugHandler;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
    }

    /**
     * @return bool
     */
    private function isDebugMode(): bool
    {
        return $this->_scopeConfig->isSetFlag(self::BASE_CONFIGURATION_PATH . self::KEY_LOGGING);
    }

    /**
     * Returns the public key.
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->_scopeConfig->getValue(self::BASE_CONFIGURATION_PATH . self::KEY_PUBLIC_KEY);
    }

    /**
     * Returns the private key.
     *
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->_scopeConfig->getValue(self::BASE_CONFIGURATION_PATH . self::KEY_PRIVATE_KEY);
    }

    /**
     * Returns the list of valid source IPs for webhook events.
     *
     * @return string[]
     */
    public function getWebhooksSourceIps(): array
    {
        return preg_split('/\s*,\s*/', $this->getValue(self::KEY_WEBHOOKS_SOURCE_IPS));
    }

    /**
     * Returns an API client using the configured private key.
     *
     * @return Heidelpay
     * @throws NoSuchEntityException
     */
    public function getHeidelpayClient(): Heidelpay
    {
        /** @var Heidelpay $client */
        $client = new Heidelpay(
            $this->getPrivateKey(),
            $this->_storeManager->getStore()->getLocaleCode()
        );

        $client->setDebugMode($this->isDebugMode());
        $client->setDebugHandler($this->_debugHandler);

        return $client;
    }
}
