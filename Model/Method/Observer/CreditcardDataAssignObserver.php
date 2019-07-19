<?php

namespace Heidelpay\Gateway2\Model\Method\Observer;

use Heidelpay\Gateway2\Model\Method\Creditcard;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class CreditcardDataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var array
     */
    protected $additionalInformationList = [
        Creditcard::KEY_RESOURCE_ID,
    ];

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
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