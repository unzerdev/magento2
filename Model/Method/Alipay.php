<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Alipay payment method
 *
 * @link  https://docs.unzer.com/
 */
class Alipay extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
