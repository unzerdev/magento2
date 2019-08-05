<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Model\Config;

class DirectDebit extends Base
{
    const CONFIG_PATH_STORE_NAME = 'general/store_information/name';

    protected $_code = Config::METHOD_DIRECT_DEBIT;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @inheritDoc
     */
    public function getFrontendConfig()
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
