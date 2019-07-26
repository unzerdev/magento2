<?php

namespace Heidelpay\Gateway2\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as BaseConfig;

class Module extends BaseConfig
{
    const METHOD_CODE = 'hpg2';

    const KEY_PUBLIC_KEY = 'public_key';
    const KEY_PRIVATE_KEY = 'private_key';

    /**
     * Module constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        if ($methodCode === null) {
            $methodCode = static::METHOD_CODE;
        }

        parent::__construct($scopeConfig, $methodCode, $pathPattern);
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
     * Returns the private key.
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->getValue(self::KEY_PRIVATE_KEY);
    }
}
