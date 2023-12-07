<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Sofort payment method
 *
 * @link  https://docs.unzer.com/
 */
class Sofort extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
