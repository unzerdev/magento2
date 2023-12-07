<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Przelewy24 payment method
 *
 * @link  https://docs.unzer.com/
 */
class Przelewy24 extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
