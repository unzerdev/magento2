<?php

namespace Heidelpay\Gateway2\Model\Method;

class DirectDebit extends Base
{
    const CONFIG_PATH_STORE_NAME = 'general/store_information/name';

    /**
     * @inheritDoc
     */
    public function getFrontendConfig(): array
    {
        $merchantName = $this->getConfigData('merchant_name');

        if (empty($merchantName)) {
            $merchantName = $this->_scopeConfig->getValue(self::CONFIG_PATH_STORE_NAME);
        }

        return [
            'merchantName' => $merchantName,
        ];
    }
}
