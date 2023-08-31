<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\InstantPurchase\PayPal;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface;

/**
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
class AvailabilityChecker implements AvailabilityCheckerInterface
{
    private const CONFIG_INSTANT_PURCHASE_ACTIVE = 'payment/unzer_paypal_vault/instant_purchase_active';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * AvailabilityChecker constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::CONFIG_INSTANT_PURCHASE_ACTIVE
        );
    }
}
