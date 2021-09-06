<?php

namespace Unzer\PAPI\Api\Data;

/**
 * Checkout API Customer DTO.
 *
 * Copyright (C) 2019 heidelpay GmbH
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
 * @link  https://docs.heidelpay.com/
 *
 * @author Justin Nuß
 *
 * @package  heidelpay/magento2-merchant-gateway
 */
class Customer
{
    /** @var string|null $id */
    protected $id;

    /** @var string $firstname */
    protected $firstname;

    /** @var string $lastname */
    protected $lastname;

    /** @var string $salutation */
    protected $salutation;

    /** @var string|null $birthDate */
    protected $birthDate;

    /** @var string|null $company*/
    protected $company;

    /** @var string $email*/
    protected $email;

    /** @var string|null $phone */
    protected $phone;

    /** @var string|null $mobile */
    protected $mobile;

    /** @var Address $billingAddress */
    protected $billingAddress;

    /** @var Address $shippingAddress */
    protected $shippingAddress;

    /** @var CompanyInfo $companyInfo */
    protected $companyInfo;

    /**
     * Customer constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param \UnzerSDK\Resources\Customer $customerResource
     * @return static
     */
    public static function fromResource(\UnzerSDK\Resources\Customer $customerResource): self
    {
        $customer = new self();
        $customer->id = $customerResource->getId();
        $customer->firstname = $customerResource->getFirstname();
        $customer->lastname = $customerResource->getLastname();
        $customer->salutation = $customerResource->getSalutation();
        $customer->birthDate = $customerResource->getBirthDate();
        $customer->company = $customerResource->getCompany();
        $customer->email = $customerResource->getEmail();
        $customer->phone = $customerResource->getPhone();
        $customer->mobile = $customerResource->getMobile();

        if ($customerResource->getBillingAddress() !== null) {
            $customer->billingAddress = Address::fromResource($customerResource->getBillingAddress());
        }

        if ($customerResource->getShippingAddress() !== null) {
            $customer->shippingAddress = Address::fromResource($customerResource->getShippingAddress());
        }

        if ($customerResource->getCompanyInfo() !== null) {
            $customer->companyInfo = CompanyInfo::fromResource($customerResource->getCompanyInfo());
        }

        return $customer;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @return string
     */
    public function getSalutation(): string
    {
        return $this->salutation;
    }

    /**
     * @return string|null
     */
    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * @return \Unzer\PAPI\Api\Data\Address|null
     */
    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    /**
     * @return \Unzer\PAPI\Api\Data\Address|null
     */
    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    /**
     * @return \Unzer\PAPI\Api\Data\CompanyInfo|null
     */
    public function getCompanyInfo(): ?\Unzer\PAPI\Api\Data\CompanyInfo
    {
        return $this->companyInfo;
    }
}
