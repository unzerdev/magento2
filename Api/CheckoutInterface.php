<?php
declare(strict_types=1);

namespace Unzer\PAPI\Api;

use Unzer\PAPI\Api\Data\CustomerInterface;

/**
 * Checkout API Interface.
 *
 * @link  https://docs.unzer.com/
 * @api
 */
interface CheckoutInterface
{
    /**
     * Returns the external customer ID for the current quote.
     *
     * @param string|null $guestEmail Customer E-Mail address.
     *
     * @return \Unzer\PAPI\Api\Data\CustomerInterface|null
     */
    public function getExternalCustomer(?string $guestEmail = null): ?\Unzer\PAPI\Api\Data\CustomerInterface;
}
