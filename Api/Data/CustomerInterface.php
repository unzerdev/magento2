<?php
declare(strict_types=1);

namespace Unzer\PAPI\Api\Data;

use Unzer\PAPI\Model\Source\Customer as CustomerResource;

/**
 * Checkout API Customer DTO.
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
interface CustomerInterface
{
    /**
     * From Resource
     *
     * @param CustomerResource $customerResource
     * @return self
     */
    public function fromResource(CustomerResource $customerResource): self;

    /**
     * Get ID
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Get Firstname
     *
     * @return string|null
     */
    public function getFirstname(): ?string;

    /**
     * Get Lastname
     *
     * @return string|null
     */
    public function getLastname(): ?string;

    /**
     * Get Salutation
     *
     * @return string|null
     */
    public function getSalutation(): ?string;

    /**
     * Get BirthDate
     *
     * @return string|null
     */
    public function getBirthDate(): ?string;

    /**
     * Get Company
     *
     * @return string|null
     */
    public function getCompany(): ?string;

    /**
     * Get Email
     *
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * Get Phone
     *
     * @return string|null
     */
    public function getPhone(): ?string;

    /**
     * Get Mobile
     *
     * @return string|null
     */
    public function getMobile(): ?string;

    /**
     * Get Billing Address
     *
     * @return \Unzer\PAPI\Api\Data\AddressInterface|null
     */
    public function getBillingAddress(): ?\Unzer\PAPI\Api\Data\AddressInterface;

    /**
     * Get Shipping Address
     *
     * @return \Unzer\PAPI\Api\Data\AddressInterface|null
     */
    public function getShippingAddress(): ?\Unzer\PAPI\Api\Data\AddressInterface;

    /**
     * Get Company Info
     *
     * @return CompanyInfoInterface|null
     */
    public function getCompanyInfo(): ?CompanyInfoInterface;

    /**
     * Get ThreatMetrixId
     *
     * @return string|null
     */
    public function getThreatMetrixId(): ?string;
}
