<?php

namespace Heidelpay\Gateway2\Model\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Handler for checking if payments can be canceled, refunded or voided
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
class CanVoidHandler implements ValueHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(array $subject, $storeId = null)
    {
        $payment = $subject['payment']->getPayment();
        return $payment instanceof Payment && ($payment->getAmountAuthorized() > 0 || $payment->getAmountPaid() > 0);
    }
}
