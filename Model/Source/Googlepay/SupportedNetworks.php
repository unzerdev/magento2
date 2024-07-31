<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Source\Googlepay;

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
        "MASTERCARD",
        "VISA"
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
