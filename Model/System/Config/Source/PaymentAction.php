<?php

namespace Heidelpay\Gateway2\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Method\AbstractMethod;

class PaymentAction implements ArrayInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            AbstractMethod::ACTION_AUTHORIZE => __('Authorize'),
            AbstractMethod::ACTION_AUTHORIZE_CAPTURE => __('Capture'),
        ];
    }
}
