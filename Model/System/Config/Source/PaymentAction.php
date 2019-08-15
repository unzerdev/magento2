<?php

namespace Heidelpay\Gateway2\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentAction implements ArrayInterface
{
    const ACTION_AUTHORIZE = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            self::ACTION_AUTHORIZE => __('Authorize'),
            self::ACTION_AUTHORIZE_CAPTURE => __('Capture'),
        ];
    }
}
