<?php

namespace Heidelpay\Gateway2\Model\ResourceModel\PaymentInformation;

use Heidelpay\Gateway2\Model\PaymentInformation;
use Heidelpay\Gateway2\Model\ResourceModel\PaymentInformation as PaymentInformationResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventObject = 'hpg2_payment_information_collection';

    /**
     * @var string
     */
    protected $_eventPrefix = 'hpg2_payment_information_collection';

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            PaymentInformation::class,
            PaymentInformationResource::class
        );
    }
}
