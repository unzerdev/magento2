<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * WeChat payment method
 *
 * @link  https://docs.unzer.com/
 */
class Wechatpay extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
