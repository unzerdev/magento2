<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\InvoiceSecured as InvoiceSecuredPaymentType;

/**
 * Invoice (secured) payment method
 *
 * @link  https://docs.unzer.com/
 *
 * @deprecated
 */
class InvoiceSecured extends Invoice
{
    /**
     * @return bool
     */
    public function isB2cOnly(): bool
    {
        return true;
    }

    /**
     * @inheridoc
     */
    public function isSecured(): bool
    {
        return true;
    }

    public function createPaymentType(): BasePaymentType
    {
        return new InvoiceSecuredPaymentType();
    }
}
