<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Cards payment method
 *
 * @link  https://docs.unzer.com/
 */
class Cards extends Base
{
    public const VAULT_CODE = 'unzer_cards_vault';

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

        $parentConfig['vault_code'] = $this->getVaultCode();
        $parentConfig['three_ds_iframe_enabled']  = (bool) $this->_scopeConfig->getValue('payment/unzer_cards_3ds_iframe/active');
        return $parentConfig;
    }

    /**
     * @inheritDoc
     */
    public function getVaultCode(): ?string
    {
        return self::VAULT_CODE;
    }

}
