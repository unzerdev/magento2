<?php

namespace Unzer\PAPI\Api\Data;

/**
 * Checkout API Address DTO.
 *
 * Copyright (C) 2021 Unzer GmbH
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
class Address
{
    /** @var string $name */
    protected $name;

    /** @var string $street */
    protected $street;

    /** @var string $state */
    protected $state;

    /** @var string $zip */
    protected $zip;

    /** @var string city */
    protected $city;

    /** @var string country */
    protected $country;

    /**
     * CompanyInfo constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param \UnzerSDK\Resources\EmbeddedResources\Address $addressResource
     * @return static
     */
    public static function fromResource(\UnzerSDK\Resources\EmbeddedResources\Address $addressResource): self
    {
        $address = new self();
        $address->name = $addressResource->getName();
        $address->street = $addressResource->getStreet();
        $address->state = $addressResource->getState();
        $address->zip = $addressResource->getZip();
        $address->city = $addressResource->getCity();
        $address->country = $addressResource->getCountry();
        return $address;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }
}
