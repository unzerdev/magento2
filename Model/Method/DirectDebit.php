<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Direct debit payment method
 *
 * @deprecated use paylater direct debit
 *
 * @link  https://docs.unzer.com/
 */
class DirectDebit extends Base
{
    public const CONFIG_PATH_STORE_NAME = 'general/store_information/name';

    /**
     * @inheritDoc
     */
    public function getFrontendConfig(): array
    {
        $parentConfig = parent::getFrontendConfig();

        $merchantName = $this->getConfigData('merchant_name');

        if (empty($merchantName)) {
            $merchantName = $this->_scopeConfig->getValue(self::CONFIG_PATH_STORE_NAME);
        }

        $parentConfig['merchantName'] = $merchantName;

        return $parentConfig;
    }
}
