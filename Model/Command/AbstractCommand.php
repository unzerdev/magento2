<?php

namespace Heidelpay\MGW\Model\Command;

use Heidelpay\MGW\Helper\Order;
use Heidelpay\MGW\Model\Config;
use Heidelpay\MGW\Model\Method\Observer\BaseDataAssignObserver;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\TransactionTypes\AbstractTransactionType;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Services\ResourceNameService;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Psr\Log\LoggerInterface;

use function get_class;

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
    public const KEY_PAYMENT_ID = 'payment_id';

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
     * @var LoggerInterface
     */
    protected $_logger;

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
     * @param LoggerInterface $logger
     * @param Order $orderHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Session $checkoutSession,
        Config $config,
        LoggerInterface $logger,
        Order $orderHelper,
        UrlInterface $urlBuilder
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_config = $config;
        $this->_logger = $logger;
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
     * @param \Magento\Sales\Model\Order $order
     * @return string|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    protected function _getCustomerId(InfoInterface $payment, \Magento\Sales\Model\Order $order): ?string
    {
        /** @var string|null $customerId */
        $customerId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_CUSTOMER_ID);

        if (empty($customerId)) {
            return null;
        }

        /** @var Customer $customer */
        $customer = $this->_getClient()->fetchCustomer($customerId);

        if (!$this->_orderHelper->validateGatewayCustomerAgainstOrder($order, $customer)) {
            throw new LocalizedException(__('Payment information does not match billing address.'));
        }

        return $customerId;
    }

    /**
     * Sets the transaction information on the given payment from an authorization or charge.
     *
     * @param OrderPayment $payment
     * @param Authorization|Charge|AbstractHeidelpayResource $resource
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _setPaymentTransaction(
        OrderPayment $payment,
        AbstractHeidelpayResource $resource
    ): void
    {
        $payment->setLastTransId($resource->getId());
        $payment->setTransactionId($resource->getId());
        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending($resource->isPending());

        $payment->setAdditionalInformation(static::KEY_PAYMENT_ID, $resource->getPaymentId());
    }

    /**
     * Writes heidelpay Ids of the transaction to order history.
     *
     * @param SalesOrder $order
     * @param AbstractTransactionType $transaction
     */
    protected function addHeidelpayIdsToHistory(SalesOrder $order, AbstractTransactionType $transaction): void
    {
        $order->addCommentToStatusHistory(
            'heidelpay ' . ResourceNameService::getClassShortName(get_class($transaction)) . ' transaction: ' .
            'UniqueId: ' . $transaction->getUniqueId() . ' | ShortId: ' . $transaction->getShortId()
        );
    }

    /**
     * Add heidelpay error messages to order history.
     *
     * @param SalesOrder $order
     * @param string $code
     * @param string $message
     */
    protected function addHeidelpayErrorToOrderHistory(SalesOrder $order, $code, $message): void {
        $order->addCommentToStatusHistory("heidelpay Error (${code}): ${message}");
    }
}
