<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Source\ApplepayV2;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @link  https://docs.unzer.com/
 */
class SupportedNetworks implements OptionSourceInterface
{
    /**
     * Supported Networks
     *
     * @var array
     */
    protected static array $networks = [
        //'amex',
        //'bancomat',
        //'bancontact',
        //'cartesBancaires',
        //'chinaUnionPay',
        //'dankort',
        //'discover',
        //'eftpos',
        //'electron',
        //'elo',
        //'girocard',
        //'interac',
        //'jcb',
        //'mada',
        //'maestro',
        'masterCard',
        //'mir',
        //'privateLabel',
        'visa',
        //'vPay'
    ];

    /**
     * Return Supported Networks
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::$networks as $network) {
            $options[] = [
                'value' => $network,
                'label' => ucfirst($network),
            ];
        }
        return $options;
    }
}
