<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Invoice (factoring) payment method
 *
 * @link  https://docs.unzer.com/
 *
 * @deprecated
 */
class InvoiceSecuredB2b extends Invoice
{
    /**
     * @inheritDoc
     */
    public function isB2bOnly(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isSecured(): bool
    {
        return true;
    }
}
