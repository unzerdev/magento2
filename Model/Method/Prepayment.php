<?php

namespace Unzer\PAPI\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * Prepayment payment method
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
 * @author magento@neusta.de
 *
 * @package  unzerdev/magento2
 */
class Prepayment extends Base
{
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Base constructor.
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $moduleConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param CommandPoolInterface|null $commandPool
     * @param ValidatorPoolInterface|null $validatorPool
     * @param CommandManagerInterface|null $commandExecutor
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        ScopeConfigInterface $scopeConfig,
        Config $moduleConfig,
        PriceCurrencyInterface $priceCurrency,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($eventManager, $valueHandlerPool, $paymentDataObjectFactory, $code, $formBlockType,
            $infoBlockType, $scopeConfig, $moduleConfig, $commandPool, $validatorPool, $commandExecutor, $logger);
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @inheritDoc
     * @throws UnzerApiException
     */
    public function getAdditionalPaymentInformation(Order $order): string
    {
        $payment = $this->_moduleConfig
            ->getUnzerClient()
            ->fetchPaymentByOrderId($order->getIncrementId());

        /** @var Charge|null $charge */
        $charge = $payment->getChargeByIndex(0);

        if ($charge === null) {
            return '';
        }

        $formattedAmount = $this->priceCurrency->format(
            $order->getTotalDue(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $order->getStoreId(),
            $order->getOrderCurrency()
        );

        return __(
            'Please transfer the amount of %1 to the following account after your order has arrived:<br /><br />'
            . 'Holder: %2<br/>'
            . 'IBAN: %3<br/>'
            . 'BIC: %4<br/><br/>'
            . '<i>Please use only this identification number as the descriptor: </i><br/>'
            . '%5',
            $formattedAmount,
            $charge->getHolder(),
            $charge->getIban(),
            $charge->getBic(),
            $charge->getDescriptor()
        );
    }
}