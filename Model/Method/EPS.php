<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * EPS payment method
 *
 * @link  https://docs.unzer.com/
 */
class EPS extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
