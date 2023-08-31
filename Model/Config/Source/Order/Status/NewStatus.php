<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;
use Unzer\PAPI\Helper\Payment;

/**
 * New Status
 *
 * Copyright (C) 2021 - today Unzer GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.unzer.com/
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
