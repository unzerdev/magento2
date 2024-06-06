<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Googlepay payment method
 *
 * @link  https://docs.unzer.com/
 */
class Googlepay extends Base
{
    private const CONFIG_UNZER_CHANNEL_ID = 'unzer_channel_id';
    private const CONFIG_MERCHANT_ID = 'merchant_id';
    private const CONFIG_MERCHANT_NAME = 'merchant_name';
    private const CONFIG_ALLOWED_CARD_NETWORKS = 'allowed_card_networks';
    private const CONFIG_ALLOW_CREDIT_CARDS = 'allow_credit_cards';
    private const CONFIG_ALLOW_PREPAID_CARDS = 'allow_prepaid_cards';
    private const CONFIG_BUTTON_COLOR = 'button_color';
    private const CONFIG_BUTTON_SIZE_MODE = 'button_size_mode';
    private const CONFIG_BUTTON_BORDER_RADIUS = 'button_border_radius';

    /**
     * @inheritDoc
     */
    public function hasRedirect(): bool
    {
        return true;
    }

    /**
     * Get Frontend Config
     *
     * @return array
     */
    public function getFrontendConfig(): array
    {
        $parentConfig = parent::getFrontendConfig();

        $parentConfig['unzer_channel_id'] = $this->getConfigData(self::CONFIG_UNZER_CHANNEL_ID);
        $parentConfig['merchant_id'] = $this->getConfigData(self::CONFIG_MERCHANT_ID);
        $parentConfig['merchant_name'] = $this->getConfigData(self::CONFIG_MERCHANT_NAME);
        $parentConfig['allowed_card_networks'] = explode(',', $this->getConfigData(self::CONFIG_ALLOWED_CARD_NETWORKS));
        $parentConfig['allow_credit_cards'] = $this->getConfigData(self::CONFIG_ALLOW_CREDIT_CARDS);
        $parentConfig['allow_prepaid_cards'] = $this->getConfigData(self::CONFIG_ALLOW_PREPAID_CARDS);
        $parentConfig['button_color'] = $this->getConfigData(self::CONFIG_BUTTON_COLOR);
        $parentConfig['button_size_mode'] = $this->getConfigData(self::CONFIG_BUTTON_SIZE_MODE);
        $parentConfig['button_border_radius'] = $this->getConfigData(self::CONFIG_BUTTON_BORDER_RADIUS);

        return $parentConfig;
    }
}
