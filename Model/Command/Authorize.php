<?php

namespace Heidelpay\MGW\Model\Command;

use Heidelpay\MGW\Model\Method\Observer\BaseDataAssignObserver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Authorize Command for payments
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
class Authorize extends AbstractCommand
{
    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var float $amount */
        $amount = $commandSubject['amount'];

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string|null $customerId */
        $customerId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_CUSTOMER_ID);

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_RESOURCE_ID);

        $authorization = $this->_getClient()->authorize(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getCallbackUrl(),
            $customerId,
            $order->getIncrementId(),
            $this->_orderHelper->createMetadata($order),
            $this->_orderHelper->createBasketForOrder($order),
            null
        );

        if ($authorization->isError()) {
            throw new LocalizedException(__('Failed to authorize payment.'));
        }

        /** @var OrderPayment $payment */
        $payment->setLastTransId($authorization->getPaymentId());
        $payment->setTransactionId($authorization->getPaymentId());

        return null;
    }
}
