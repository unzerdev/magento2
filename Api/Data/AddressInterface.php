<?php
declare(strict_types=1);

namespace Unzer\PAPI\Api\Data;

use UnzerSDK\Resources\EmbeddedResources\Address as AddressResource;

/**
 * Checkout API Address DTO.
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
interface AddressInterface
{
    /**
     * From Resource
     *
     * @param AddressResource $addressResource
     * @return self
     */
    public function fromResource(AddressResource $addressResource): self;

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Get Street
     *
     * @return string|null
     */
    public function getStreet(): ?string;

    /**
     * Get State
     *
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * Get Zip
     *
     * @return string|null
     */
    public function getZip(): ?string;

    /**
     * Get City
     *
     * @return string|null
     */
    public function getCity(): ?string;

    /**
     * Get Country
     *
     * @return string|null
     */
    public function getCountry(): ?string;
}
