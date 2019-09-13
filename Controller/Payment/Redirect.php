<?php

namespace Heidelpay\MGW\Controller\Payment;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Magento\Sales\Model\Order;

/**
 * Action for redirecting customers to payment providers
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
class Redirect extends AbstractPaymentAction
{
    /**
     * @inheritDoc
     */
    public function executeWith(Order $order, Payment $payment)
    {
        $transaction = $payment->getAuthorization();

        if (!$transaction instanceof Authorization) {
            $transaction = $payment->getChargeByIndex(0);
        }

        /** @var string|null $redirectUrl */
        $redirectUrl = $transaction->getRedirectUrl() ?? $transaction->getReturnUrl();

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($redirectUrl);
        return $redirect;
    }
}
