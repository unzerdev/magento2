<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;

class Creditcard extends Base
{
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

        $this->_getClient()->authorize(
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

        /** @var Order $order */
        $order = $payment->getOrder();
        $order->setState(Order::STATE_PROCESSING);

        /** @var Payment $hpPayment */
        $hpPayment = $this->_getClient()->fetchPaymentByOrderId($order->getIncrementId());

        /** @var Authorization $hpAuthorization */
        $hpAuthorization = null;

        if ($hpPayment !== null &&
            $hpPayment->getId() !== null) {
            $hpAuthorization = $hpPayment->getAuthorization();
        }

        if ($hpAuthorization !== null &&
            $hpAuthorization->getId() !== null) {
            $hpAuthorization->charge($amount);
        } else {
            $this->_captureDirect($payment, $amount);
        }

        return $this;
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

        $this->_getClient()->charge(
            $amount,
            $order->getOrderCurrencyCode(),
            $resourceId,
            $this->_getUrl('checkout/onepage/success'),
            $order->getCustomerId(),
            $order->getIncrementId(),
            null,
            $this->_getBasketFromOrder($order),
            null,
            null,
            null
        );
    }
}