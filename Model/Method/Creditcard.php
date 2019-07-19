<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;

class Creditcard extends Base
{
    const KEY_PAYMENT_ID = 'payment_id';
    const KEY_RESOURCE_ID = 'resource_id';

    protected $_code = Config::METHOD_CREDITCARD;

    /**
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Authorize payment abstract method
     *
     * @param DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     * @throws HeidelpayApiException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
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
        $order->setState(Order::STATE_PENDING_PAYMENT);

        $authorization = $this->_getClient()->authorize(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getUrl('checkout/onepage/success'),
            $order->getCustomerId(),
            $order->getIncrementId(),
            null,
            $this->_getBasketFromOrder($order),
            null
        );

        $payment->setAdditionalInformation(self::KEY_PAYMENT_ID, $authorization->getPaymentId());
        return $this;
    }

    /**
     * Capture payment abstract method
     *
     * @param DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     * @throws HeidelpayApiException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('The capture action is not available.'));
        }

        /** @var string|null $paymentId */
        $paymentId = $payment->getAdditionalInformation(self::KEY_PAYMENT_ID);

        /** @var Order $order */
        $order = $payment->getOrder();
        $order->setState(Order::STATE_PROCESSING);

        if ($paymentId !== null) {
            $this->_captureAuthorization($payment, $amount);
        } else {
            $this->_captureDirect($payment, $amount);
        }

        return $this;
    }

    /**
     * Captures a payment from an existing authorization.
     *
     * @param InfoInterface $payment
     * @param $amount
     * @throws HeidelpayApiException
     */
    protected function _captureAuthorization(InfoInterface $payment, $amount)
    {
        /** @var string|null $paymentId */
        $paymentId = $payment->getAdditionalInformation(self::KEY_PAYMENT_ID);

        $client = $this->_getClient();
        $client->chargeAuthorization($paymentId, $amount);
    }

    /**
     * Captures a payment with an direct charge.
     *
     * @param InfoInterface $payment
     * @param $amount
     * @throws HeidelpayApiException
     */
    protected function _captureDirect(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(self::KEY_RESOURCE_ID);

        $charge = $this->_getClient()->charge(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getUrl('checkout/onepage/success'),
            $order->getCustomerId(),
            $order->getIncrementId(),
            $metadata = null,
            $this->_getBasketFromOrder($order),
            null,
            null,
            null
        );

        $payment->setAdditionalInformation(self::KEY_PAYMENT_ID, $charge->getPaymentId());
    }
}