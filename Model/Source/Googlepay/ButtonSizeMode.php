<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Source\Googlepay;

use Magento\Framework\Data\OptionSourceInterface;

class ButtonSizeMode implements OptionSourceInterface
{
    /**
     * Google Pay Button Size Mode
     *
     * @var array
     */
    protected static array $buttonSizeModes = [
        "static",
        "fill",
    ];

    /**
     * Return Google Pay Button Size Mode
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::$buttonSizeModes as $mode) {
            $options[] = [
                'value' => $mode,
                'label' => $mode,
            ];
        }
        return $options;
    }
}
