<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Unzer Bank Transfer payment method
 *
 * @link  https://docs.unzer.com/
 */
class BankTransfer extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
