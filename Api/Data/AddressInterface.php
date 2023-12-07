<?php
declare(strict_types=1);

namespace Unzer\PAPI\Api\Data;

/**
 * Checkout API Address DTO.
 *
 * @link  https://docs.unzer.com/
 * @api
 */
interface AddressInterface
{
    /**
     * From Resource
     *
     * @param \UnzerSDK\Resources\EmbeddedResources\Address $addressResource
     * @return self
     */
    public function fromResource(\UnzerSDK\Resources\EmbeddedResources\Address $addressResource): self;

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
