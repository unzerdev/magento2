<?php

namespace Unzer\PAPI\Model\Command;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Helper\Order;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Observer\BaseDataAssignObserver;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Services\ResourceNameService;
use UnzerSDK\Unzer;
use function get_class;

/**
 * Abstract Command for using the Unzer SDK
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
abstract class AbstractCommand implements CommandInterface
{
    public const KEY_PAYMENT_ID = 'payment_id';

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Unzer
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * AbstractCommand constructor.
     * @param Session $checkoutSession
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Order $orderHelper
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Session $checkoutSession,
        Config $config,
        LoggerInterface $logger,
        Order $orderHelper,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_config = $config;
        $this->_logger = $logger;
        $this->_orderHelper = $orderHelper;
        $this->_urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns the URL to which customers are redirected after payment.
     *
     * @return string
     */
    protected function _getCallbackUrl(): string
    {
        return $this->_urlBuilder->getUrl('unzer/payment/callback');
    }

    /**
     * @param string|null $storeCode
     * @return Unzer
     */
    protected function _getClient(string $storeCode = null): Unzer
    {
        if ($this->_client === null) {
            $this->_client = $this->_config->getUnzerClient($storeCode);
        }

        return $this->_client;
    }

    /**
     * Returns the customer ID for given current payment or quote. Creates or update customer on Api side if needed.
     * An UnzerApiException is thrown when customer creation/update fails.
     *
     * @param InfoInterface $payment
     * @param \Magento\Sales\Model\Order $order
     *
     * @return string|null
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     */
    protected function _getCustomerId(InfoInterface $payment, \Magento\Sales\Model\Order $order): ?string
    {
        /** @var string|null $customerId */
        $customerId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_CUSTOMER_ID);

        if (empty($customerId)) {
            $papiCustomer = $this->_orderHelper->createCustomerFromOrder($order, $order->getCustomerEmail(), true);
            $customerId = $papiCustomer->getId();
        }

        /** @var Customer $customer */
        $customer = $this->_getClient()->fetchCustomer($customerId);

        if (!$this->_orderHelper->validateGatewayCustomerAgainstOrder($order, $customer)) {
            $this->_orderHelper->updateGatewayCustomerFromOrder($order, $customer);
        }

        return $customerId;
    }

    /**
     * Sets the transaction information on the given payment from an authorization or charge.
     *
     * @param OrderPayment $payment
     * @param Authorization|Charge|AbstractUnzerResource $resource
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _setPaymentTransaction(
        OrderPayment $payment,
        AbstractUnzerResource $resource
    ): void
    {
        $payment->setLastTransId($resource->getId());
        $payment->setTransactionId($resource->getId());
        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending($resource->isPending());

        $payment->setAdditionalInformation(static::KEY_PAYMENT_ID, $resource->getPaymentId());
    }

    /**
     * Writes Unzer Ids of the transaction to order history.
     *
     * @param SalesOrder $order
     * @param AbstractTransactionType $transaction
     */
    protected function addUnzerpayIdsToHistory(SalesOrder $order, AbstractTransactionType $transaction): void
    {
        $order->addCommentToStatusHistory(
            'Unzer ' . ResourceNameService::getClassShortName(get_class($transaction)) . ' transaction: ' .
            'UniqueId: ' . $transaction->getUniqueId() . ' | ShortId: ' . $transaction->getShortId()
        );
    }

    /**
     * Add Unzer error messages to order history.
     *
     * @param SalesOrder $order
     * @param string $code
     * @param string $message
     */
    protected function addUnzerErrorToOrderHistory(SalesOrder $order, $code, $message): void {
        $order->addCommentToStatusHistory("Unzer Error (${code}): ${message}");
    }

    /**
     * @param int $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode(int $storeId)
    {
        return $this->storeManager->getStore($storeId)->getCode();
    }
}
