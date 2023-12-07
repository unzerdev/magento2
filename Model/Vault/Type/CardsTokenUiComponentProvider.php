<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Vault\Type;

use IntlDateFormatter;
use JsonException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\System\Config\CreditCardBrand;

/**
 * Cards Token uiComponent Provider
 *
 * @link  https://docs.unzer.com/
 */
class CardsTokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private TokenUiComponentInterfaceFactory $componentFactory;

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
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param TimezoneInterface $timezoneInterface
     * @param CreditCardBrand $creditCardBrand
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        TimezoneInterface $timezoneInterface,
        CreditCardBrand $creditCardBrand
    ) {
        $this->componentFactory = $componentFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->creditCardBrand = $creditCardBrand;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     * @throws JsonException
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken): TokenUiComponentInterface
    {
        $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        $jsonDetails['formattedExpirationDate'] = $this->timezoneInterface->formatDate(
            $jsonDetails['expirationDate'],
            IntlDateFormatter::LONG
        );

        $jsonDetails['cardBrand'] = $this->creditCardBrand->getBrandByType($jsonDetails['type']);

        return $this->componentFactory->create(
            [
                'config' => [
                    'code' => Config::METHOD_CARDS_VAULT,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash()
                ],
                'name' => 'Unzer_PAPI/js/view/payment/method-renderer/cards_vault'
            ]
        );
    }
}
