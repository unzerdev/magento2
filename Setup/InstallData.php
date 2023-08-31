<?php
declare(strict_types=1);

namespace Unzer\PAPI\Setup;

use Unzer\PAPI\Helper\Payment;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\StatusFactory;

/**
 * Module Data Setup
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
class InstallData implements InstallDataInterface
{
    private const STATUS_READY_TO_CAPTURE_LABEL = 'Ready to Capture';

    /**
     * @var StatusFactory
     */
    private StatusFactory $_orderStatusFactory;

    /**
     * InstallData constructor.
     * @param StatusFactory $orderStatusFactory
     */
    public function __construct(StatusFactory $orderStatusFactory)
    {
        $this->_orderStatusFactory = $orderStatusFactory;
    }

    /**
     * Install
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Exception
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            $status = $this->_orderStatusFactory->create();
            $status->setStatus(Payment::STATUS_READY_TO_CAPTURE);
            $status->setData('label', self::STATUS_READY_TO_CAPTURE_LABEL);
            $status->save();
        }
    }
}
