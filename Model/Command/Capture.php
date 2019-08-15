<?php

namespace Heidelpay\Gateway2\Model\Command;

use Heidelpay\Gateway2\Model\Method\Observer\BaseDataAssignObserver;
use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

class Capture extends AbstractCommand
{
    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws HeidelpayApiException
     */
    public function execute(array $commandSubject)
    {
        /** @var InfoInterface $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var float $amount */
        $amount = $commandSubject['amount'];

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

        /** @var OrderPayment $payment */
        $payment->setLastTransId($charge->getPaymentId());
        $payment->setTransactionId($charge->getPaymentId());

        return null;
    }

    /**
     * Charges an existing payment.
     *
     * @param Payment $payment
     * @param float $amount
     * @return Charge
     * @throws HeidelpayApiException
     */
    protected function _chargeExisting(Payment $payment, float $amount): Charge
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _chargeNew(InfoInterface $payment, float $amount): Charge
    {
        /** @var string|null $customerId */
        $customerId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_CUSTOMER_ID);

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_RESOURCE_ID);

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
}
