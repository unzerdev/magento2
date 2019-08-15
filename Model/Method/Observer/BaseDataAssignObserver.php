<?php

namespace Heidelpay\Gateway2\Model\Method\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class BaseDataAssignObserver extends AbstractDataAssignObserver
{
    const KEY_CUSTOMER_ID = 'customer_id';
    const KEY_RESOURCE_ID = 'resource_id';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::KEY_CUSTOMER_ID,
        self::KEY_RESOURCE_ID,
    ];

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var DataObject $data */
        $data = $this->readDataArgument($observer);

        /** @var array $additionalData */
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        /** @var InfoInterface $paymentInfo */
        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
