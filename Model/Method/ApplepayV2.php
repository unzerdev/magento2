<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Apple Pay V2 payment method
 *
 * @link  https://docs.unzer.com/
 */
class ApplepayV2 extends Base
{
    /**
     * @inheritDoc
     */
    public function getFrontendConfig(): array
    {
        $supportedNetworks = (string) $this->_scopeConfig->getValue('payment/unzer/applepayv2/supported_networks');
        $supportedNetworks = explode(',', $supportedNetworks);

        return [
            'supportedNetworks' => $supportedNetworks,
            'merchantCapabilities' => ['supports3DS'],
            'label' => 'Unzer GmbH'
        ];
    }
}
