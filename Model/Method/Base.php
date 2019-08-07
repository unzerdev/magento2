<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Helper\Order as OrderHelper;
use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Pricing\PriceCurrencyInterface;
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
    const KEY_CUSTOMER_ID = 'customer_id';
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
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * @var OrderHelper
     */
    protected $_orderHelper;

    /**
     * @var Order\Payment\Processor
     */
    protected $_paymentProcessor;

    /**
     * @var PriceCurrencyInterface
     */
    protected $_priceCurrency;

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
     * @param CheckoutSession $checkoutSession
     * @param Config $moduleConfig
     * @param OrderHelper $orderHelper
     * @param Order\Payment\Processor $paymentProcessor
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreInterface $store
     * @param UrlInterface $urlBuilder
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
        CheckoutSession $checkoutSession,
        Config $moduleConfig,
        OrderHelper $orderHelper,
        Order\Payment\Processor $paymentProcessor,
        PriceCurrencyInterface $priceCurrency,
        StoreInterface $store,
        UrlInterface $urlBuilder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    ) {
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

        $this->_checkoutSession = $checkoutSession;
        $this->_moduleConfig = $moduleConfig;
        $this->_orderHelper = $orderHelper;
        $this->_paymentProcessor = $paymentProcessor;
        $this->_priceCurrency = $priceCurrency;
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
            $this->_client = $this->_moduleConfig->getHeidelpayClient();
        }

        return $this->_client;
    }

    /**
     * @return string
     */
    protected function _getCallbackUrl()
    {
        return $this->_urlBuilder->getUrl('hpg2/payment/callback');
    }

    /**
     * @inheritDoc
     */
    public function initialize($paymentAction, $stateObject)
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $this->getInfoInstance();

        /** @var Order $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE:
                $this->_paymentProcessor->authorize($payment, true, $order->getTotalDue());
                break;
            case self::ACTION_AUTHORIZE_CAPTURE:
                $this->_paymentProcessor->capture($payment, null);
                break;
            default:
                break;
        }
    }

    /**
     * @inheritDoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new LocalizedException(__('The authorize action is not available.'));
        }

        /** @var string|null $customerId */
        $customerId = $payment->getAdditionalInformation(self::KEY_CUSTOMER_ID);

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(self::KEY_RESOURCE_ID);

        /** @var Order $order */
        $order = $payment->getOrder();

        $authorization = $this->_getClient()->authorize(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getCallbackUrl(),
            $customerId,
            $order->getIncrementId(),
            $this->_orderHelper->createMetadata($order),
            $this->_orderHelper->createBasketForOrder($order),
            null
        );

        if ($authorization->isError()) {
            throw new LocalizedException(__('Failed to authorize payment.'));
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws HeidelpayApiException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('The capture action is not available.'));
        }

        /** @var Order $order */
        $order = $payment->getOrder();

        try {
            $hpPayment = $this->_getClient()->fetchPaymentByOrderId($order->getIncrementId());
        } catch (HeidelpayApiException $e) {
            if ($e->getCode() !== ApiResponseCodes::API_ERROR_PAYMENT_NOT_FOUND) {
                throw $e;
            }

            $hpPayment = null;
        }

        if ($hpPayment !== null) {
            $charge = $this->_chargeExisting($hpPayment, $amount);
        } else {
            $charge = $this->_chargeNew($payment, $amount);
        }

        if ($charge->isError()) {
            throw new LocalizedException(__('Failed to charge payment.'));
        }

        /** @var OrderPaymentInterface $payment */
        $payment->setLastTransId($charge->getShortId());

        return $this;
    }

    /**
     * Charges an existing payment.
     *
     * @param Payment $payment
     * @param $amount
     * @return Charge
     * @throws HeidelpayApiException
     */
    protected function _chargeExisting(Payment $payment, $amount)
    {
        /** @var Authorization|null $authorization */
        $authorization = $payment->getAuthorization();

        if ($authorization !== null) {
            return $authorization->charge($amount);
        }

        return $payment->getChargeByIndex(0);
    }

    /**
     * Charges a new payment.
     *
     * @param InfoInterface $payment
     * @param $amount
     * @return Charge
     * @throws HeidelpayApiException
     */
    protected function _chargeNew(InfoInterface $payment, $amount)
    {
        /** @var string|null $customerId */
        $customerId = $payment->getAdditionalInformation(self::KEY_CUSTOMER_ID);

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(self::KEY_RESOURCE_ID);

        return $this->_getClient()->charge(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getCallbackUrl(),
            $customerId,
            $order->getIncrementId(),
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
        $hpPayment = $this->_getClient()->fetchPaymentByOrderId($order->getIncrementId());

        /** @var Cancellation $refund */
        $cancellation = $hpPayment->cancel($amount);

        if ($cancellation->isError()) {
            throw new LocalizedException(__('Failed to refund payment.'));
        }

        return $this;
    }

    /**
     * Returns additional payment information for the customer.
     *
     * @param Order $order
     *
     * @return string
     */
    public function getAdditionalPaymentInformation(Order $order)
    {
        return '';
    }
}
