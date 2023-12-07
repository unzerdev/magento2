<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Paylater Installment payment method
 *
 * @link  https://docs.unzer.com/
 */
class PaylaterInstallment extends Base implements OverrideApiCredentialInterface
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
