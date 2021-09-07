<?php

namespace Unzer\PAPI\Block\Info;

use Unzer\PAPI\Helper\Order as OrderHelper;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Block\Info;
use Magento\Sales\Model\Order;

/**
 * Customer Account Order Invoice Information Block
 *
 * Copyright (C) 2021 Unzer GmbH
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
class Invoice extends Info
{
    protected $_template = 'Unzer_PAPI::info/invoice.phtml';

    /**
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * @var Payment
     */
    protected $_payment = null;

    /**
     * Invoice constructor.
     * @param Template\Context $context
     * @param Config $moduleConfig
     * @param OrderHelper $orderHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $moduleConfig,
        OrderHelper $orderHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_moduleConfig = $moduleConfig;
    }

    /**
     * @inheritDoc
     */
    public function toPdf(): string
    {
        $this->setTemplate('Unzer_PAPI::info/pdf/invoice.phtml');
        return $this->toHtml();
    }

    /**
     * Returns the first charge for the payment.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     *
     * @return Charge|null
     */
    protected function _getCharge()
    {
        return $this->_getPayment()->getChargeByIndex(0);
    }

    /**
     * Returns the payment.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     *
     * @return Payment
     */
    protected function _getPayment(): Payment
    {
        if ($this->_payment === null) {
            /** @var Order $order */
            $order = $this->getInfo()->getOrder();

            $storeId = $this->getStoreCode($order->getStoreId());
            $client  = $this->_moduleConfig->getUnzerClient($storeId);

            $this->_payment = $client->fetchPaymentByOrderId($order->getIncrementId());
        }

        return $this->_payment;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     * @return string
     */
    public function getAccountHolder(): string
    {
        return $this->_getCharge()->getHolder();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     * @return string
     */
    public function getAccountIban(): string
    {
        return $this->_getCharge()->getIban();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     * @return string
     */
    public function getAccountBic(): string
    {
        return $this->_getCharge()->getBic();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     * @return string
     */
    public function getReference(): string
    {
        return $this->_getCharge()->getDescriptor();
    }

    /**
     * Returns the order for the invoice.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        $order = $this->_getData('order');
        if ($order) {
            return $order;
        }
        return $this->getInfo()->getOrder();
    }

    /**
     * @param string|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode(string $storeId = null)
    {
        return $this->_storeManager->getStore($storeId)->getCode();
    }
}
