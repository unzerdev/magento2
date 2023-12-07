<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Source;

use UnzerSDK\Resources\Customer as UnzerCustomer;

/**
 * This represents the customer resource.
 *
 * @link  https://docs.unzer.com/
 */
class Customer extends UnzerCustomer
{
    /** @var string|null $threatMetrixId */
    protected ?string $threatMetrixId;

    /**
     * Set Threat Metrix ID
     *
     * @param string|null $threatMetrixId
     * @return Customer
     */
    public function setThreatMetrixId(?string $threatMetrixId): Customer
    {
        $this->threatMetrixId = $threatMetrixId;
        return $this;
    }

    /**
     * Get Threat Metrix ID
     *
     * @return string|null
     */
    public function getThreatMetrixId(): ?string
    {
        return $this->threatMetrixId;
    }
}
