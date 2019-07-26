<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Helper\Order as OrderHelper;
use Heidelpay\Gateway2\Model\Config;
use Heidelpay\Gateway2\Model\PaymentInformation;
use Heidelpay\Gateway2\Model\PaymentInformationFactory;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;

class Base extends AbstractMethod
{
    const KEY_RESOURCE_ID = 'resource_id';

    protected $_code = Config::METHOD_BASE;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var boolean
     */
    protected $_canUseInternal = false;

    /**
     * @var Heidelpay
     */
    protected $_client;

    /**
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * @var OrderHelper
     */
    protected $_orderHelper;

    /**
     * @var PaymentInformationFactory
     */
    protected $_paymentInformationFactory;

    /**
     * @var StoreInterface
     */
    protected $_store;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * Base constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param Config $moduleConfig
     * @param StoreInterface $store
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param DirectoryHelper|null $directory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Config $moduleConfig,
        OrderHelper $orderHelper,
        PaymentInformationFactory $paymentInformationFactory,
        StoreInterface $store,
        UrlInterface $urlBuilder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );

        $this->_moduleConfig = $moduleConfig;
        $this->_orderHelper = $orderHelper;
        $this->_paymentInformationFactory = $paymentInformationFactory;
        $this->_store = $store;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Returns the configuration for the checkout page.
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        return [];
    }

    /**
     * Returns the gateway client.
     *
     * @return Heidelpay
     */
    protected function _getClient()
    {
        if ($this->_client === null) {
            $this->_client = $this->_moduleConfig->getHeidelpayClient($this->_store->getLocaleCode());
        }

        return $this->_client;
    }

    /**
     * Returns an absolute URL for the given route.
     *
     * @param string $routePath
     *
     * @return string
     */
    protected function _getUrl($routePath)
    {
        return $this->_urlBuilder->getUrl($routePath);
    }

    /**
     * @return string
     */
    protected function _getAuthorizationCallbackUrl()
    {
        return $this->_getUrl('hpg2/payment/authorizationCallback');
    }

    /**
     * @return string
     */
    protected function _getChargeCallbackUrl()
    {
        return $this->_getUrl('hpg2/payment/chargeCallback');
    }

    /**
     * @inheritDoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new LocalizedException(__('The authorize action is not available.'));
        }

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(self::KEY_RESOURCE_ID);

        /** @var Order $order */
        $order = $payment->getOrder();

        $authorization = $this->_getClient()->authorize(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getAuthorizationCallbackUrl(),
            $this->_orderHelper->createOrUpdateCustomerForOrder($order),
            $this->_orderHelper->getExternalId($order),
            $this->_orderHelper->createMetadata($order),
            $this->_orderHelper->createBasketForOrder($order),
            null
        );

        if ($authorization->isError()) {
            throw new LocalizedException(__('Failed to authorize payment.'));
        }

        /** @var PaymentInformation $paymentInformation */
        $paymentInformation = $this->_paymentInformationFactory->create();
        $paymentInformation->load($authorization->getPaymentId(), 'payment_id');
        $paymentInformation->setOrder($order);
        $paymentInformation->setTransaction($authorization);
        $paymentInformation->save();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('The capture action is not available.'));
        }

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var PaymentInformation $paymentInformation */
        $paymentInformation = $this->_paymentInformationFactory->create();
        $paymentInformation->load($this->_orderHelper->getExternalId($order), 'external_id');

        if ($paymentInformation->getPaymentId() !== null) {
            $charge = $this->_getClient()->chargeAuthorization($paymentInformation->getPaymentId(), $amount);
        } else {
            $charge = $this->_captureDirect($payment, $amount);
        }

        if ($charge->isError()) {
            throw new LocalizedException(__('Failed to charge payment.'));
        }

        $paymentInformation->setOrder($order);
        $paymentInformation->setTransaction($charge);
        $paymentInformation->save();

        /** @var OrderPaymentInterface $payment */
        $payment->setLastTransId($paymentInformation->getExternalId());

        return $this;
    }

    /**
     * Captures a payment with an direct charge.
     *
     * @param InfoInterface $payment
     * @param $amount
     * @return Charge
     * @throws HeidelpayApiException
     */
    protected function _captureDirect(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(self::KEY_RESOURCE_ID);

        return $this->_getClient()->charge(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getChargeCallbackUrl(),
            $this->_orderHelper->createOrUpdateCustomerForOrder($order),
            $this->_orderHelper->getExternalId($order),
            $this->_orderHelper->createMetadata($order),
            $this->_orderHelper->createBasketForOrder($order),
            null,
            null,
            null
        );
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function cancel(InfoInterface $payment)
    {
        return $this->refund($payment, null);
    }

    /**
     * @inheritDoc
     */
    public function refund(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var Payment $hpPayment */
        $hpPayment = $this->_getClient()->fetchPaymentByOrderId($this->_orderHelper->getExternalId($order));

        /** @var Cancellation $refund */
        $cancellation = $hpPayment->cancel($amount);

        if ($cancellation->isError()) {
            throw new LocalizedException(__('Failed to refund payment.'));
        }

        return $this;
    }
}