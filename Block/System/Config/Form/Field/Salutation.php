<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config\Form\Field;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Customer Account Order Prepayment Information Block
 *
 * @link  https://docs.unzer.com/
 */
class Salutation implements OptionSourceInterface
{
    public const SALUTATION_UNKNOWN = 'unknown';
    public const SALUTATION_MR = 'mr';
    public const SALUTATION_MRS = 'mrs';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            self::SALUTATION_UNKNOWN => __('Unknown'),
            self::SALUTATION_MRS => __('mrs'),
            self::SALUTATION_MR => __('mr'),
        ];
    }
}
