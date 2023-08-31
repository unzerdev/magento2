<?php
declare(strict_types=1);

namespace Unzer\PAPI\Api\Data;

use UnzerSDK\Resources\EmbeddedResources\RiskData as UnzerRiskData;

/**
 * Create Risk Data Interface
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
 * @api
 */
interface CreateRiskDataInterface
{

    public const CUSTOMER_GROUP_TOP = 'TOP';
    public const CUSTOMER_GROUP_GOOD = 'GOOD';
    public const CUSTOMER_GROUP_BAD = 'BAD';
    public const CUSTOMER_GROUP_NEUTRAL = 'NEUTRAL';
    public const REGISTRATION_LEVEL_GUEST = '0';
    public const REGISTRATION_LEVEL_CUSTOMER = '1';

    /**
     * Create Unzer Risk Data Object and assign data to it
     *
     * @return UnzerRiskData|null
     * @see \Unzer\PAPI\Model\Checkout\Data\CreateRiskdata
     *
     */
    public function execute(): ?UnzerRiskData;
}
