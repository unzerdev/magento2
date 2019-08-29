<?php

namespace Heidelpay\MGW\Model\Command;

use heidelpayPHP\Constants\CancelReasonCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;

/**
 * Cancel Command for payments
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
class Cancel extends AbstractCommand
{
    const REASON = CancelReasonCodes::REASON_CODE_CANCEL;

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws HeidelpayApiException
     */
    public function execute(array $commandSubject)
    {
        /** @var InfoInterface $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var float $amountToCancel */
        $amountToCancel = $commandSubject['amount'] ?? $order->getGrandTotal();

        /** @var Payment $hpPayment */
        $hpPayment = $this->_getClient()->fetchPaymentByOrderId($order->getIncrementId());

        $chargeCount = count($hpPayment->getCharges());

        if ($chargeCount === 0) {
            /** @var Cancellation $cancellation */
            $cancellation = $hpPayment->getAuthorization()->cancel($amountToCancel);
            if ($cancellation->isError()) {
                throw new LocalizedException(__('Failed to cancel payment.'));
            }
            return;
        }

        for ($index = $chargeCount - 1; $index >= 0 && $amountToCancel > 0; $index--) {
            /** @var Charge $charge */
            $charge = $hpPayment->getChargeByIndex($index);

            /** @var Cancellation $cancellation */
            if ($charge->getAmount() >= $amountToCancel) {
                $cancellation = $charge->cancel($amountToCancel, static::REASON);
            } else {
                $cancellation = $charge->cancel(null, static::REASON);
            }

            if ($cancellation->isError()) {
                throw new LocalizedException(__('Failed to cancel payment.'));
            }

            $amountToCancel -= $cancellation->getAmount();
        }
    }
}
