<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Paypal payment method
 *
 * @link  https://docs.unzer.com/
 */
class Paypal extends Base
{
    public const VAULT_CODE = 'unzer_paypal_vault';

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
    public function getFrontendConfig(): array
    {
        $parentConfig = parent::getFrontendConfig();

        $parentConfig['vault_code'] = $this->getVaultCode();

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
