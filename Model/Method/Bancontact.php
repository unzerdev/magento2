<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Bancontact payment method
 *
 * @link  https://docs.unzer.com/
 */
class Bancontact extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
