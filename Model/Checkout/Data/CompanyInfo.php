<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Checkout\Data;

use Unzer\PAPI\Api\Data\CompanyInfoInterface;

/**
 * Checkout API CompanyInfo DTO.
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
     * @param \UnzerSDK\Resources\EmbeddedResources\CompanyInfo $companyInfoResource
     * @return self
     */
    public function fromResource(\UnzerSDK\Resources\EmbeddedResources\CompanyInfo $companyInfoResource): self
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
