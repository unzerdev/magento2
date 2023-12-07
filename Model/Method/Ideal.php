<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Ideal payment method
 *
 * @link  https://docs.unzer.com/
 */
class Ideal extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
