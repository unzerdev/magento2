<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Source\Googlepay;

use Magento\Framework\Data\OptionSourceInterface;

class ButtonColor implements OptionSourceInterface
{
    /**
     * Google Pay Button Colors
     *
     * @var array
     */
    protected static array $buttonColors = [
        "default",
        "black",
        "white",
    ];

    /**
     * Return Google Pay Button Colors
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::$buttonColors as $color) {
            $options[] = [
                'value' => $color,
                'label' =>  $color,
            ];
        }
        return $options;
    }
}
