<?php

namespace Heidelpay\MGW\Model\Command;

use Heidelpay\MGW\Model\Method\Observer\BaseDataAssignObserver;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Capture Command for payments
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
class Capture extends AbstractCommand
{
    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws HeidelpayApiException
     */
    public function execute(array $commandSubject)
    {
        /** @var OrderPayment $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var float $amount */
        $amount = $commandSubject['amount'];

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string|null $paymentId */
        $paymentId = $payment->getAdditionalInformation(self::KEY_PAYMENT_ID);

        try {
            if ($paymentId !== null) {
                $charge = $this->_chargeExisting($paymentId, $amount);
            } else {
                $charge = $this->_chargeNew($payment, $amount);
            }
        } catch (HeidelpayApiException $e) {
            $this->_logger->error($e->getMerchantMessage(), ['incrementId' => $order->getIncrementId()]);
            throw new LocalizedException(__($e->getClientMessage()));
        }

        if ($charge->isError()) {
            throw new LocalizedException(__('Failed to charge payment.'));
        }

        $this->_setPaymentTransaction(
            $payment,
            $charge,
            $charge->getPayment()->getAuthorization()
        );
        return null;
    }

    /**
     * Charges an existing payment.
     *
     * @param string $paymentId
     * @param float $amount
     * @return Charge
     * @throws HeidelpayApiException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _chargeExisting(string $paymentId, float $amount): Charge
    {
        /** @var Payment $payment */
        $payment = $this->_getClient()->fetchPayment($paymentId);

        /** @var Authorization|null $authorization */
        $authorization = $payment->getAuthorization();

        if ($authorization !== null) {
            return $authorization->charge($amount);
        }

        return $payment->charge($amount);
    }

    /**
     * Charges a new payment.
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return Charge
     * @throws HeidelpayApiException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws LocalizedException
     */
    protected function _chargeNew(InfoInterface $payment, float $amount): Charge
    {
        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_RESOURCE_ID);

        return $this->_getClient()->charge(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getCallbackUrl(),
            $this->_getCustomerId($payment, $order),
            $order->getIncrementId(),
            $this->_orderHelper->createMetadataForOrder($order),
            $this->_orderHelper->createBasketForOrder($order),
            null,
            null,
            null
        );
    }
}
