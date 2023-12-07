<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Payment action source for adminhtml select fields and initializable payment methods
 *
 * @link  https://docs.unzer.com/
 */
class PaymentAction implements OptionSourceInterface
{
    public const ACTION_AUTHORIZE = 'authorize';
    public const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            self::ACTION_AUTHORIZE => __('Authorize'),
            self::ACTION_AUTHORIZE_CAPTURE => __('Charge'),
        ];
    }
}
