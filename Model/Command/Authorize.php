<?php

namespace Unzer\PAPI\Model\Command;

use Unzer\PAPI\Model\Method\Observer\BaseDataAssignObserver;
use UnzerSDK\Exceptions\UnzerApiException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Authorize Command for payments
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
class Authorize extends AbstractCommand
{
    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     */
    public function execute(array $commandSubject)
    {
        /** @var OrderPayment $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var float $amount */
        $amount = $commandSubject['amount'];

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_RESOURCE_ID);

        try {
            $authorization = $this->_getClient()->authorize(
                $amount,
                $order->getOrderCurrencyCode(),
                $resourceId,
                $this->_getCallbackUrl(),
                $this->_getCustomerId($payment, $order),
                $order->getIncrementId(),
                $this->_orderHelper->createMetadataForOrder($order),
                $this->_orderHelper->createBasketForOrder($order),
                null
            );
            $order->addCommentToStatusHistory('Unzer paymentId: ' . $authorization->getPaymentId());
        } catch (UnzerApiException $e) {
            $this->_logger->error($e->getMerchantMessage(), ['incrementId' => $order->getIncrementId()]);
            throw new LocalizedException(__($e->getClientMessage()));
        }

        $this->addUnzerpayIdsToHistory($order, $authorization);

        if ($authorization->isError()) {
            throw new LocalizedException(__('Failed to authorize payment.'));
        }

        $this->_setPaymentTransaction($payment, $authorization);
        return null;
    }
}
