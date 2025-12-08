<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\InstantPurchase\DirectDebit;

use InvalidArgumentException;
use JsonException;
use Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * @link  https://docs.unzer.com/
 */
class TokenFormatter implements PaymentTokenFormatterInterface
{
    /**
     * @inheritdoc
     *
     * @throws JsonException
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        if (!isset($details['maskedIban'], $details['accountHolder'])) {
            throw new InvalidArgumentException('Invalid Unzer SEPA Direct Debit token details.');
        }

        return sprintf(
            '%s: %s (%s)',
            __('SEPA Direct Debit'),
            $details['maskedIban'],
            $details['accountHolder']
        );
    }
}
