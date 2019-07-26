<?php

namespace Heidelpay\Gateway2\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PaymentInformation extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'hpg2_payment_information';

    /**
     * @internal
     */
    protected function _construct()
    {
        $this->_init('heidelpay_gateway2_payment_information', 'id');
    }
}
