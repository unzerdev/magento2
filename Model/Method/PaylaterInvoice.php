<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 *
 * @link  https://docs.unzer.com/
 */
class PaylaterInvoice extends Invoice implements OverrideApiCredentialInterface
{
    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasRiskData(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isB2cOnly(): bool
    {
        return true;
    }
}
