<?php

declare(strict_types=1);

namespace Unzer\PAPI\Model\Source\Googlepay;

use Magento\Framework\Data\OptionSourceInterface;

class CountryCode implements OptionSourceInterface
{
    /**
     * Supported Networks
     *
     * @var array
     */
    protected static array $networks = [
        "DK" => "Denmark",
        "CH" => "Switzerland",
    ];

    /**
     * Return Supported Networks
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::$networks as $code => $name) {
            $options[] = [
                'value' => $code,
                'label' => __($name),
            ];
        }
        return $options;
    }
}
