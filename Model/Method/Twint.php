<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Twint payment method
 *
 * @link  https://docs.unzer.com/
 */
class Twint extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
