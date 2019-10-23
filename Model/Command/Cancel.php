<?php

namespace Heidelpay\MGW\Model\Command;

use heidelpayPHP\Constants\CancelReasonCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Framework\Exception\LocalizedException;
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
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var float $amountToCancel */
        $amountToCancel = $commandSubject['amount'] ?? $order->getGrandTotal();

        /** @var Payment $hpPayment */
        $hpPayment = $this->_getClient()->fetchPaymentByOrderId($order->getIncrementId());

        if ($hpPayment->isCanceled()) {
            return;
        }

        if (count($hpPayment->getCharges()) === 0) {
            $cancellation = $this->cancelAuthorization($hpPayment, $amountToCancel);
        } else {
            $cancellation = $this->cancelCharges($hpPayment, $amountToCancel);
        }

        $payment->setLastTransId($cancellation->getId());
    }

    /**
     * @param Payment $hpPayment
     * @param float $amountToCancel
     * @return Cancellation
     * @throws HeidelpayApiException
     * @throws LocalizedException
     */
    private function cancelAuthorization(Payment $hpPayment, float $amountToCancel): Cancellation
    {
        /** @var Authorization $authorization */
        $authorization = $hpPayment->getAuthorization();

        /** @var Cancellation $cancellation */
        $cancellation = $authorization->cancel($amountToCancel);
        if ($cancellation->isError()) {
            throw new LocalizedException(__('Failed to cancel authorization.'));
        }

        return $cancellation;
    }

    /**
     * @param Charge $charge
     * @param float $amountToCancel
     * @return Cancellation
     * @throws HeidelpayApiException
     * @throws LocalizedException
     */
    private function cancelCharge(Charge $charge, float $amountToCancel): Cancellation
    {
        /** @var Cancellation $cancellation */
        if ($charge->getAmount() >= $amountToCancel) {
            $cancellation = $charge->cancel($amountToCancel, static::REASON);
        } else {
            $cancellation = $charge->cancel(null, static::REASON);
        }

        if ($cancellation->isError()) {
            throw new LocalizedException(__('Failed to cancel charge.'));
        }

        return $cancellation;
    }

    /**
     * @param Payment $hpPayment
     * @param float $amountToCancel
     * @return Cancellation
     * @throws HeidelpayApiException
     * @throws LocalizedException
     */
    private function cancelCharges(Payment $hpPayment, float $amountToCancel): Cancellation
    {
        $chargeCount = count($hpPayment->getCharges());

        /** @var Cancellation $cancellation */

        for ($index = $chargeCount - 1; $index >= 0 && $amountToCancel > 0; $index--) {
            /** @var Charge $charge */
            $charge = $hpPayment->getChargeByIndex($index);
            $cancellation = $this->cancelCharge($charge, $amountToCancel);
            $amountToCancel -= $cancellation->getAmount();
        }

        return $cancellation;
    }
}
