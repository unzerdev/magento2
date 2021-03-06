<?php

namespace Unzer\PAPI\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Validators\PrivateKeyValidator;
use UnzerSDK\Validators\PublicKeyValidator;

/**
 * Abstract base payment method
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
class Base extends Adapter
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * Base constructor.
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param ScopeConfigInterface $scopeConfig
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
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    )
    {
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );

        $this->_scopeConfig = $scopeConfig;
        $this->_moduleConfig = $moduleConfig;
    }

    /**
     * Returns the configuration for the checkout page.
     *
     * @return array
     */
    public function getFrontendConfig(): array
    {
        return [];
    }

    /**
     * Returns additional payment information for the customer.
     *
     * @param Order $order
     * @return string
     */
    public function getAdditionalPaymentInformation(Order $order): string
    {
        return '';
    }

    /**
     * Returns whether a redirect is required when making a payment.
     *
     * @return bool
     */
    public function hasRedirect(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $moduleConfig = $this->_moduleConfig;
        if ($quote === null) {
            return parent::isAvailable($quote);
        }

        $isPrivateKeyValid = PrivateKeyValidator::validate($moduleConfig->getPrivateKey());
        $isPublicKeyValid = PublicKeyValidator::validate($moduleConfig->getPublicKey());
        if (!$isPrivateKeyValid || !$isPublicKeyValid) {
            return false;
        }

        if ($quote->getIsVirtual() && $this->isSecured()) {
            return false;
        }

        $hasCompany = !empty($quote->getBillingAddress()->getCompany());

        if ($hasCompany) {
            if ($this->isB2cOnly()) {
                return false;
            }
        } elseif ($this->isB2bOnly()) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Returns whether the payment method is only available for B2B customers.
     *
     * @return bool
     */
    public function isB2bOnly(): bool
    {
        return false;
    }

    /**
     * Returns whether the payment method is only available for B2C customers.
     *
     * @return bool
     */
    public function isB2cOnly(): bool
    {
        return false;
    }

    /**
     * Returns whether the payment method is safe.
     *
     * @return bool
     */
    public function isSecured(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return __(parent::getTitle());
    }


}
