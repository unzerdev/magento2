<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

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
interface OverrideApiCredentialInterface
{

    /**
     * Has Method Valid Override Keys
     *
     * @param string|null $storeId
     * @return bool
     */
    public function hasMethodValidOverrideKeys(string $storeId = null): bool;

    /**
     * Get Method Override Public Key
     *
     * @param string|null $storeId
     * @return string
     */
    public function getMethodOverridePublicKey(string $storeId = null): string;

    /**
     * Get Method Override Private Key
     *
     * @param string|null $storeId
     * @return string
     */
    public function getMethodOverridePrivateKey(string $storeId = null): string;
}
