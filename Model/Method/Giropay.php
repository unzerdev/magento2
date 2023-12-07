<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Giropay payment method
 *
 * @link  https://docs.unzer.com/
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
