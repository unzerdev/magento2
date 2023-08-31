<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;

/**
 * Handler for checking if payments can be voided
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
 */
class CanVoidHandler implements ValueHandlerInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function handle(array $subject, $storeId = null)
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $subject['payment']->getPayment();
        if (!$payment instanceof Payment) {
            return false;
        }

        if (!$this->canVoid($payment)) {
            return false;
        }

        return (float)$payment->getBaseAmountAuthorized() > (float)$payment->getBaseAmountPaid();
    }

    /**
     * Can Void
     *
     * @param Payment $payment
     * @return bool
     * @throws LocalizedException
     */
    private function canVoid(Payment $payment): bool
    {
        $storeId = $payment->getOrder()->getStoreId();

        $path = 'payment/' . $payment->getMethodInstance()->getCode() . '/' . 'can_void';
        return (bool)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
