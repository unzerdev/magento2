<?php

namespace Heidelpay\Gateway2\Model\Method\Observer;

use Heidelpay\Gateway2\Model\Method\DirectDebitGuaranteed;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

/**
 * Observer for restricting payment methods to B2C customers
 *
 * Copyright (C) 2019 heidelpay GmbH
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
 * @link  https://docs.heidelpay.com/
 *
 * @author Justin Nuß
 *
 * @package  heidelpay/magento2-merchant-gateway
 */
class B2CAvailabilityObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var MethodInterface $methodInstance */
        $methodInstance = $observer->getEvent()->getData('method_instance');

        if (!$methodInstance instanceof DirectDebitGuaranteed) {
            return;
        }

        /** @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        $isAvailable = $quote === null || empty($quote->getBillingAddress()->getCompany());

        $resultObject = $observer->getEvent()->getData('result');
        $resultObject->setData('is_available', $isAvailable);
    }
}
