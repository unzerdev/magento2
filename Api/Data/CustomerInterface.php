<?php
declare(strict_types=1);

namespace Unzer\PAPI\Api\Data;

/**
 * Checkout API Customer DTO.
 *
 * @link  https://docs.unzer.com/
 * @api
 */
interface CustomerInterface
{
    /**
     * From Resource
     *
     * @param \Unzer\PAPI\Model\Source\Customer $customerResource
     * @return self
     */
    public function fromResource(\Unzer\PAPI\Model\Source\Customer $customerResource): self;

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
     * @return \Unzer\PAPI\Api\Data\CompanyInfoInterface|null
     */
    public function getCompanyInfo(): ?\Unzer\PAPI\Api\Data\CompanyInfoInterface;

    /**
     * Get ThreatMetrixId
     *
     * @return string|null
     */
    public function getThreatMetrixId(): ?string;
}
