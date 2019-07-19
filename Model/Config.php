<?php

namespace Heidelpay\Gateway2\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const KEY_PUBLIC_KEY = 'public_key';
    const KEY_PRIVATE_KEY = 'private_key';

    const METHOD_BASE = 'hpg2';
    const METHOD_CREDITCARD = 'hpg2_creditcard';

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * Module constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Returns the public key.
     *
     * @return string
     */
    public function getPublicKey()
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
    protected function getValue($field, $storeId = null)
    {
        return $this->_scopeConfig->getValue('payment/hpg2/' . $field, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the private key.
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->getValue(self::KEY_PRIVATE_KEY);
    }
}
