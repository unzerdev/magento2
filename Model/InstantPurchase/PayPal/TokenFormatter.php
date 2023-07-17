<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\InstantPurchase\PayPal;

use InvalidArgumentException;
use JsonException;
use Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Unzer vaulted PayPal token formatter
 *
 * Class TokenFormatter
 */
class TokenFormatter implements PaymentTokenFormatterInterface
{
    /**
     * @inheritdoc
     * @throws JsonException
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        if (!isset($details['payerEmail'])) {
            throw new InvalidArgumentException('Invalid Unzer PayPal token details.');
        }

        return sprintf(
            '%s: %s',
            __('PayPal'),
            $details['payerEmail']
        );
    }
}
