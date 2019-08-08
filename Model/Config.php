<?php

namespace Heidelpay\Gateway2\Model;

use Heidelpay\Gateway2\Model\Logger\DebugHandler;
use heidelpayPHP\Heidelpay;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
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
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    private $_debugHandler;

    /**
     * Module constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        DebugHandler $debugHandler
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_debugHandler = $debugHandler;
    }

    /**
     * Returns the public key.
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->getValue(self::KEY_PUBLIC_KEY);
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|null $storeId
     *
     * @return mixed
     */
    protected function getValue(string $field, $storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'payment/hpg2/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the private key.
     *
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->getValue(self::KEY_PRIVATE_KEY);
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getHeidelpayClient(): Heidelpay
    {
        $heidelPay = new Heidelpay(
            $this->getPrivateKey(),
            $this->_storeManager->getStore()->getLocaleCode()
        );
        return $this->activateDebuggingInPayment($heidelPay);
    }

    /**
     * Retrive is debug setup activated.
     *
     * @return bool
     */
    public function isDebugActivated(): bool
    {
        return !!$this->getValue(self::KEY_LOGGING);
    }

    /**
     * Activate logger function.
     *
     * @param Heidelpay $heidelPay Payment object.
     *
     * @return Heidelpay
     */
    protected function activateDebuggingInPayment(Heidelpay $heidelPay): Heidelpay
    {
        if ($this->isDebugActivated()) {
            $heidelPay->setDebugMode(true)
                ->setDebugHandler($this->_debugHandler);

        }
        return $heidelPay;
    }
}
