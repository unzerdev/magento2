<?php

namespace Heidelpay\Gateway2\Config\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Payment\Gateway\Config\Config as BaseConfig;

abstract class Base extends BaseConfig
{
    const METHOD_CODE = 'hpg2_';

    const KEY_ACTIVE = 'active';
    const KEY_TITLE = 'title';
    const KEY_MIN_ORDER_TOTAL = 'min_order_total';
    const KEY_MAX_ORDER_TOTAL = 'max_order_total';

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
    )
    {
        if ($methodCode === null) {
            $methodCode = static::METHOD_CODE;
        }

        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    /**
     * Returns whether the payment method is active.
     *
     * @return bool
     */
    public function getActive()
    {
        return boolval($this->getValue(self::KEY_ACTIVE));
    }

    /**
     * Returns the payment method title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getValue(self::KEY_TITLE);
    }

    /**
     * Returns the minimum order total for the payment method to be usable.
     *
     * @return float
     */
    public function getMinOrderTotal()
    {
        return floatval($this->getValue(self::KEY_MIN_ORDER_TOTAL));
    }

    /**
     * Returns the maximum order total for the payment method to be usable.
     *
     * @return float
     */
    public function getMaxOrderTotal()
    {
        $maxOrderTotal = floatval($this->getValue(self::KEY_MAX_ORDER_TOTAL));
        if ($maxOrderTotal <= 0.0) {
            $maxOrderTotal = PHP_FLOAT_MAX;
        }
        return $maxOrderTotal;
    }
}