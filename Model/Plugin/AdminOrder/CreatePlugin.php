<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Plugin\AdminOrder;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Unzer\PAPI\Helper\Payment as PaymentHelper;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use UnzerSDK\Exceptions\UnzerApiException;

/**
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
class CreatePlugin
{
    /**
     * @var Config
     */
    private Config $moduleConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var PaymentHelper
     */
    private PaymentHelper $paymentHelper;

    /**
     * Constructor
     *
     * @param Config $moduleConfig
     * @param StoreManagerInterface $storeManager
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Config $moduleConfig,
        StoreManagerInterface $storeManager,
        PaymentHelper $paymentHelper
    ) {

        $this->moduleConfig = $moduleConfig;
        $this->storeManager = $storeManager;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * After Create Order
     *
     * @param Create $create
     * @param Order $order
     * @return Order
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function afterCreateOrder(Create $create, Order $order): Order
    {
        $payment = $order->getPayment();

        $storeCode = $this->storeManager->getStore($order->getStoreId())->getCode();

        $methodInstance = $payment->getMethodInstance();

        if (!$methodInstance instanceof Base) {
            return $order;
        }

        $payment = $this->moduleConfig
            ->getUnzerClient($storeCode, $methodInstance)
            ->fetchPaymentByOrderId($order->getIncrementId());

        $this->paymentHelper->processState($order, $payment);

        return $order;
    }
}
