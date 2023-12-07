<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\InstantPurchase\CreditCard;

use IntlDateFormatter;
use InvalidArgumentException;
use JsonException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Unzer\PAPI\Model\System\Config\CreditCardBrand;

/**
 * @link  https://docs.unzer.com/
 */
class TokenFormatter implements PaymentTokenFormatterInterface
{
    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @var CreditCardBrand
     */
    private CreditCardBrand $creditCardBrand;

    /**
     * Constructor
     *
     * @param TimezoneInterface $timezoneInterface
     * @param CreditCardBrand $creditCardBrand
     */
    public function __construct(
        TimezoneInterface $timezoneInterface,
        CreditCardBrand $creditCardBrand
    ) {
        $this->timezoneInterface = $timezoneInterface;
        $this->creditCardBrand = $creditCardBrand;
    }

    /**
     * @inheritdoc
     *
     * @throws JsonException
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        if (!isset($details['type'], $details['maskedCC'], $details['expirationDate'])) {
            throw new InvalidArgumentException('Invalid Unzer credit card token details.');
        }

        $ccType = $this->creditCardBrand->getBrandByType($details['type']);

        return sprintf(
            '%s: %s, %s (%s: %s)',
            __('Credit Card'),
            $ccType,
            $details['maskedCC'],
            __('expires'),
            $this->timezoneInterface->formatDate($details['expirationDate'], IntlDateFormatter::LONG)
        );
    }
}
