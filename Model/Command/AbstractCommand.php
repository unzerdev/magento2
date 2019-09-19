<?php

namespace Heidelpay\MGW\Model\Command;

use Exception;
use Heidelpay\MGW\Helper\Order;
use Heidelpay\MGW\Model\Config;
use Heidelpay\MGW\Model\Method\Observer\BaseDataAssignObserver;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Abstract Command for using the heidelpay SDK
 *
 * Copyright (C) 2019 heidelpay GmbH
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
 * @link  https://docs.heidelpay.com/
 *
 * @author Justin Nuß
 *
 * @package  heidelpay/magento2-merchant-gateway
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Heidelpay
     */
    protected $_client;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var Order
     */
    protected $_orderHelper;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * AbstractCommand constructor.
     * @param Session $checkoutSession
     * @param Config $config
     * @param Order $orderHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Session $checkoutSession,
        Config $config,
        Order $orderHelper,
        UrlInterface $urlBuilder
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_config = $config;
        $this->_orderHelper = $orderHelper;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Returns the URL to which customers are redirected after payment.
     *
     * @return string
     */
    protected function _getCallbackUrl(): string
    {
        return $this->_urlBuilder->getUrl('hpmgw/payment/callback');
    }

    /**
     * @return Heidelpay
     * @throws NoSuchEntityException
     */
    protected function _getClient(): Heidelpay
    {
        if ($this->_client === null) {
            $this->_client = $this->_config->getHeidelpayClient();
        }

        return $this->_client;
    }

    /**
     * Returns the customer ID for given current payment or quote.
     *
     * @param InfoInterface $payment
     * @return string|null
     */
    protected function _getCustomerId(InfoInterface $payment): ?string
    {
        /** @var string|null $customerId */
        $customerId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_CUSTOMER_ID);

        if (!empty($customerId)) {
            return $customerId;
        }

        try {
            $customer = $this->_orderHelper->createOrUpdateCustomerFromQuote(
                $this->_checkoutSession->getQuote(),
                $this->_checkoutSession->getQuote()->getCustomerEmail()
            );

            if ($customer !== null) {
                return $customer->getId();
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Sets the transaction information on the given payment from an authorization or charge.
     *
     * @param OrderPayment $payment
     * @param Authorization|Charge|AbstractHeidelpayResource $resource
     * @param Authorization|Charge|AbstractHeidelpayResource|null $parentResource
     *
     * @return void
     */
    protected function _setPaymentTransaction(
        OrderPayment $payment,
        AbstractHeidelpayResource $resource,
        ?AbstractHeidelpayResource $parentResource = null
    ): void
    {
        $payment->setLastTransId($resource->getUniqueId());
        $payment->setTransactionId($resource->getUniqueId());
        $payment->setIsTransactionClosed(!$resource->isPending());
        $payment->setIsTransactionPending($resource->isPending());

        if ($parentResource !== null) {
            $payment->setParentTransactionId($parentResource->getUniqueId());
        }
    }
}
