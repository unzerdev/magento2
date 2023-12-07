<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Direct debit (secured) payment method
 *
 * @link  https://docs.unzer.com/
 */
class DirectDebitSecured extends DirectDebit
{
    /**
     * @inheritDoc
     */
    public function isB2cOnly(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isSecured(): bool
    {
        return true;
    }
}
