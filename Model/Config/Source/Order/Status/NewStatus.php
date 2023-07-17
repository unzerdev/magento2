<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;
use Unzer\PAPI\Helper\Payment;

/**
 * Order Statuses source model
 */
class NewStatus extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_PROCESSING,
        Payment::STATUS_READY_TO_CAPTURE
    ];

    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $statuses = $this->_stateStatuses
            ? $this->_orderConfig->getStateStatuses($this->_stateStatuses)
            : $this->_orderConfig->getStatuses();

        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        foreach ($statuses as $code => $label) {
            if (in_array($code, $this->_stateStatuses, true)) {
                $options[] = ['value' => $code, 'label' => $label];
            }
        }
        return $options;
    }
}
