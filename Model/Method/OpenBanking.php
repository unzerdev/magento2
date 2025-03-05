<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Unzer Open Banking payment method
 *
 * @link  https://docs.unzer.com/
 */
class OpenBanking extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
