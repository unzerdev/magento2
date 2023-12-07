<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Vault\Type;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Unzer\PAPI\Model\Config;

/**
 * PayPal Token uiComponent Provider
 *
 * @link  https://docs.unzer.com/
 */
class PaypalTokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private TokenUiComponentInterfaceFactory $componentFactory;

    /**
     * Constructor
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory
    ) {
        $this->componentFactory = $componentFactory;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     * @throws \JsonException
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken): TokenUiComponentInterface
    {
        $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        return $this->componentFactory->create(
            [
                'config' => [
                    'code' => Config::METHOD_PAYPAL_VAULT,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash()
                ],
                'name' => 'Unzer_PAPI/js/view/payment/method-renderer/paypal_vault'
            ]
        );
    }
}
