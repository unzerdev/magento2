<?php

namespace Unzer\PAPI\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Payment action source for adminhtml select fields and initializable payment methods
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
 *
 * @author Justin Nuß
 *
 * @package  unzerdev/magento2
 */
class PaymentAction implements OptionSourceInterface
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
            self::ACTION_AUTHORIZE_CAPTURE => __('Charge'),
        ];
    }
}
