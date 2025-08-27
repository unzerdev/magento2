<?php

namespace Unzer\PAPI\Model\Method;

/**
 * Class Klarna.
 *
 * @package Unzer\PAPI\Model\Method
 */
class Klarna extends Base
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }
}
