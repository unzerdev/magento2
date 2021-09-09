<?php

namespace Unzer\PAPI\Api\Data;

/**
 * Checkout API CompanyInfo DTO.
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
class CompanyInfo
{
    /** @var string $registrationType */
    protected $registrationType;

    /** @var string|null $commercialRegisterNumber */
    protected $commercialRegisterNumber;

    /** @var string|null $function */
    protected $function;

    /** @var string $commercialSector */
    protected $commercialSector;

    /**
     * CompanyInfo constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param \UnzerSDK\Resources\EmbeddedResources\CompanyInfo $companyInfoResource
     * @return static
     */
    public static function fromResource(\UnzerSDK\Resources\EmbeddedResources\CompanyInfo $companyInfoResource): self
    {
        $companyInfo = new self();
        $companyInfo->registrationType = $companyInfoResource->getRegistrationType();
        $companyInfo->commercialRegisterNumber = $companyInfoResource->getCommercialRegisterNumber();
        $companyInfo->function = $companyInfoResource->getFunction();
        $companyInfo->commercialSector = $companyInfoResource->getCommercialSector();
        return $companyInfo;
    }

    /**
     * @return string
     */
    public function getRegistrationType(): string
    {
        return $this->registrationType;
    }

    /**
     * @return string|null
     */
    public function getCommercialRegisterNumber(): ?string
    {
        return $this->commercialRegisterNumber;
    }

    /**
     * @return string|null
     */
    public function getFunction(): ?string
    {
        return $this->function;
    }

    /**
     * @return string
     */
    public function getCommercialSector(): string
    {
        return $this->commercialSector;
    }
}
