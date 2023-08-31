<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\System\Config;

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
class CreditCardBrand
{
    /**
     * Most used credit card types
     * @var array
     */
    public static array $baseCardTypes = [
        'MASTER' => 'MasterCard',
        'VISA' => 'Visa',
    ];

    /**
     * Get credit card brand by type
     *
     * @param string $type
     * @return string
     */
    public function getBrandByType(string $type): string
    {
        return self::$baseCardTypes[$type] ?? $type;
    }
}
