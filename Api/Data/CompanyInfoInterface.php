<?php
declare(strict_types=1);

namespace Unzer\PAPI\Api\Data;

/**
 * Checkout API CompanyInfo DTO.
 *
 * @link  https://docs.unzer.com/
 * @api
 */
interface CompanyInfoInterface
{
    /**
     * From Resource
     *
     * @param \UnzerSDK\Resources\EmbeddedResources\CompanyInfo $companyInfoResource
     * @return self
     */
    public function fromResource(\UnzerSDK\Resources\EmbeddedResources\CompanyInfo $companyInfoResource): self;

    /**
     * Get Registration Type
     *
     * @return string|null
     */
    public function getRegistrationType(): ?string;

    /**
     * Get Commercial Register Number
     *
     * @return string|null
     */
    public function getCommercialRegisterNumber(): ?string;

    /**
     * Get Function
     *
     * @return string|null
     */
    public function getFunction(): ?string;

    /**
     * Get Commercial Sector
     *
     * @return string|null
     */
    public function getCommercialSector(): ?string;
}
