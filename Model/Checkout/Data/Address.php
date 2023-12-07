<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Checkout\Data;

use Unzer\PAPI\Api\Data\AddressInterface;

/**
 * Checkout API Address DTO.
 *
 * @link  https://docs.unzer.com/
 */
class Address implements AddressInterface
{
    /** @var string|null $name */
    protected ?string $name;

    /** @var string|null $street */
    protected ?string $street;

    /** @var string|null $state */
    protected ?string $state;

    /** @var string|null $zip */
    protected ?string $zip;

    /** @var string|null city */
    protected ?string $city;

    /** @var string|null country */
    protected ?string $country;

    /**
     * From Resource
     *
     * @param \UnzerSDK\Resources\EmbeddedResources\Address $addressResource
     * @return self
     */
    public function fromResource(\UnzerSDK\Resources\EmbeddedResources\Address $addressResource): self
    {
        $this->name = $addressResource->getName();
        $this->street = $addressResource->getStreet();
        $this->state = $addressResource->getState();
        $this->zip = $addressResource->getZip();
        $this->city = $addressResource->getCity();
        $this->country = $addressResource->getCountry();
        return $this;
    }

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get Street
     *
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * Get State
     *
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Get Zip
     *
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * Get City
     *
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Get Country
     *
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }
}
