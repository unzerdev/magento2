<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * @link  https://docs.unzer.com/
 */
class SupportedNetworks implements ArrayInterface
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
        'maestro',
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
                'label' => $network,
            ];
        }
        return $options;
    }
}
