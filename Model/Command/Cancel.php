<?php

namespace Unzer\PAPI\Model\Command;

use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

/**
 * Cancel Command for payments
 *
 * Copyright (C) 2021 Unzer GmbH
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
class Cancel extends AbstractCommand
{
    const REASON = CancelReasonCodes::REASON_CODE_CANCEL;

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function execute(array $commandSubject)
    {
        /** @var Order\Payment $payment */
        $payment = $commandSubject['payment']->getPayment();

        $order = $payment->getOrder();

        $storeCode = $this->getStoreCode($order->getStoreId());

        $hpPayment = $this->_getClient($storeCode)->fetchPaymentByOrderId($order->getIncrementId());

        if ($hpPayment->isCanceled()) {
            return;
        }

        $cancellations = $hpPayment->cancelAmount($commandSubject['amount'] ?? null, static::REASON);

        if (count($cancellations) > 0) {
            $lastCancellation = end($cancellations);
            $payment->setLastTransId($lastCancellation->getId());
        }
    }
}
