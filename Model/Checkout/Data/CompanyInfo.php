<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Checkout\Data;

use Unzer\PAPI\Api\Data\CompanyInfoInterface;
use UnzerSDK\Resources\EmbeddedResources\CompanyInfo as CompanyInfoResource;

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
 */
class CompanyInfo implements CompanyInfoInterface
{
    /** @var string|null $registrationType */
    protected ?string $registrationType;

    /** @var string|null $commercialRegisterNumber */
    protected ?string $commercialRegisterNumber;

    /** @var string|null $function */
    protected ?string $function;

    /** @var string|null $commercialSector */
    protected ?string $commercialSector;

    /**
     * From Resource
     *
     * @param CompanyInfoResource $companyInfoResource
     * @return self
     */
    public function fromResource(CompanyInfoResource $companyInfoResource): self
    {
        $this->registrationType = $companyInfoResource->getRegistrationType();
        $this->commercialRegisterNumber = $companyInfoResource->getCommercialRegisterNumber();
        $this->function = $companyInfoResource->getFunction();
        $this->commercialSector = $companyInfoResource->getCommercialSector();
        return $this;
    }

    /**
     * Get Registration Type
     *
     * @return string|null
     */
    public function getRegistrationType(): ?string
    {
        return $this->registrationType;
    }

    /**
     * Get Commercial Register Number
     *
     * @return string|null
     */
    public function getCommercialRegisterNumber(): ?string
    {
        return $this->commercialRegisterNumber;
    }

    /**
     * Get Function
     *
     * @return string|null
     */
    public function getFunction(): ?string
    {
        return $this->function;
    }

    /**
     * Get Commercial Sector
     *
     * @return string|null
     */
    public function getCommercialSector(): ?string
    {
        return $this->commercialSector;
    }
}
