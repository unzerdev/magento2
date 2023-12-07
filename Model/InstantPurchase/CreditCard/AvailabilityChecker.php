<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\InstantPurchase\CreditCard;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface;

/**
 * @link  https://docs.unzer.com/
 */
class AvailabilityChecker implements AvailabilityCheckerInterface
{
    private const CONFIG_INSTANT_PURCHASE_ACTIVE = 'payment/unzer_cards_vault/instant_purchase_active';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * AvailabilityChecker constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::CONFIG_INSTANT_PURCHASE_ACTIVE
        );
    }
}
