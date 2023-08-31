<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Block\Info;
use Magento\Sales\Model\Order;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * Customer Account Order Prepayment Information Block
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
class Prepayment extends Info
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::info/prepayment.phtml';

    /**
     * @var Config
     */
    protected Config $_moduleConfig;

    /**
     * @var Payment|null
     */
    protected ?Payment $_payment = null;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Config $moduleConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $moduleConfig,
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
        $this->setTemplate('Unzer_PAPI::info/pdf/prepayment.phtml');
        return $this->toHtml();
    }

    /**
     * Get Initial Transaction
     *
     * @return Authorization|Charge|null
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    protected function getInitialTransaction()
    {
        $transaction = $this->_getPayment()->getInitialTransaction();

        if ($transaction instanceof Authorization || $transaction instanceof Charge) {
            return $transaction;
        }
        return null;
    }

    /**
     * Has Account Data
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function hasAccountData(): bool
    {
        return $this->getInitialTransaction() !== null;
    }

    /**
     * Get Payment
     *
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    protected function _getPayment(): Payment
    {
        if ($this->_payment === null) {
            /** @var Order $order */
            $order = $this->getInfo()->getOrder();

            $client = $this->_moduleConfig->getUnzerClient(
                $order->getStore()->getCode(),
                $order->getPayment()->getMethodInstance()
            );

            $this->_payment = $client->fetchPaymentByOrderId($order->getIncrementId());
        }

        return $this->_payment;
    }

    /**
     * Get Account Holder
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function getAccountHolder(): ?string
    {
        $initialTransaction = $this->getInitialTransaction();
        if ($initialTransaction === null) {
            return null;
        }

        return $initialTransaction->getHolder();
    }

    /**
     * Get Account Iban
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function getAccountIban(): ?string
    {
        $initialTransaction = $this->getInitialTransaction();
        if ($initialTransaction === null) {
            return null;
        }
        return $initialTransaction->getIban();
    }

    /**
     * Get Account Bic
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function getAccountBic(): ?string
    {
        $initialTransaction = $this->getInitialTransaction();
        if ($initialTransaction === null) {
            return null;
        }
        return $initialTransaction->getBic();
    }

    /**
     * Get Reference
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function getReference(): ?string
    {
        $initialTransaction = $this->getInitialTransaction();
        if ($initialTransaction === null) {
            return null;
        }
        return $initialTransaction->getDescriptor();
    }

    /**
     * Get Order
     *
     * @throws LocalizedException
     */
    public function getOrder(): Order
    {
        $order = $this->_getData('order');
        if ($order) {
            return $order;
        }
        return $this->getInfo()->getOrder();
    }
}
