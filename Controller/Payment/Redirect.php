<?php

namespace Unzer\PAPI\Controller\Payment;

use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Magento\Sales\Model\Order;

/**
 * Action for redirecting customers to payment providers
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
class Redirect extends AbstractPaymentAction
{
    /**
     * @inheritDoc
     */
    public function executeWith(Order $order, Payment $payment)
    {
        /** @var Authorization|Charge $transaction */
        $transaction = $payment->getAuthorization() ?? $payment->getChargeByIndex(0);

        if ($transaction->isError()) {
            return $this->abortCheckout($transaction->getMessage()->getCustomer());
        }

        $this->_paymentHelper->setOrderState($order, Order::STATE_NEW, 'pending');

        /** @var string|null $redirectUrl */
        $redirectUrl = $transaction->getRedirectUrl();

        if (empty($redirectUrl)) {
            $redirectUrl = $transaction->getReturnUrl();
        }

        return $this->_redirect($redirectUrl);
    }
}
