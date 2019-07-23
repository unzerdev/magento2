<?php

namespace Heidelpay\Gateway2\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Method\AbstractMethod;

class PaymentAction implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            AbstractMethod::ACTION_AUTHORIZE => __('Authorize'),
            AbstractMethod::ACTION_AUTHORIZE_CAPTURE => __('Capture'),
        ];
    }
}