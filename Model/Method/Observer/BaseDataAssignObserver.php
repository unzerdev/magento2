<?php

namespace Unzer\PAPI\Model\Method\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Observer for assigning additional payment information from the frontend to payments
 *
 * Copyright (C) 2021 - today Unzer GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.unzer.com/
 *
 * @author Justin Nuß
 *
 * @package  unzerdev/magento2
 */
class BaseDataAssignObserver extends AbstractDataAssignObserver
{
    const KEY_CUSTOMER_ID = 'customer_id';
    const KEY_RESOURCE_ID = 'resource_id';
    const KEY_THREAT_METRIX_ID = 'threat_metrix_id';
    const KEY_BIRTHDATE = 'birthDate';
    const KEY_SALUTATION = 'salutation';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::KEY_CUSTOMER_ID,
        self::KEY_RESOURCE_ID,
        self::KEY_BIRTHDATE,
        self::KEY_SALUTATION,
        self::KEY_THREAT_METRIX_ID
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
