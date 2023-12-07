<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * Apple Pay payment method
 *
 * @link  https://docs.unzer.com/
 */
class Applepay extends Base
{
    /**
     * @inheritDoc
     */
    public function getFrontendConfig(): array
    {
        //@todo serialize?
        $supportedNetworks = (string) $this->_scopeConfig->getValue('payment/unzer/applepay/supported_networks');
        $supportedNetworks = explode(',', $supportedNetworks);

        return [
            'supportedNetworks' => $supportedNetworks,
            'merchantCapabilities' => ['supports3DS'],
            'label' => $this->_scopeConfig->getValue('payment/unzer_applepay/display_name') //label
        ];
    }
}
