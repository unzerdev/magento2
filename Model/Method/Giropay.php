<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Giropay payment method
 *
 * @link  https://docs.unzer.com/
 * @deprecated
 * @see https://docs.unzer.com/payment-methods/giropay/
 */
class Giropay extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
