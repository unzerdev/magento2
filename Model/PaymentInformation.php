<?php

namespace Heidelpay\Gateway2\Model;

use heidelpayPHP\Resources\TransactionTypes\AbstractTransactionType;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\Order;

/**
 * @method string|null getExternalId()
 * @method $this setExternalId(string|null $externalId)
 *
 * @method string|null getPaymentId()
 * @method $this setPaymentId(string|null $paymentId)
 *
 * @method int|null getStoreId()
 * @method $this setStoreId(int|null $externalId)
 *
 * @method int|null getOrderId()
 * @method $this setOrderId(int|null $orderId)
 *
 * @method string|null getOrderIncrementId()
 * @method $this setOrderIncrementId(string|null $orderIncrementId)
 *
 * @method string|null getRedirectUrl()
 * @method $this setRedirectUrl(string|null $redirectUrl)
 */
class PaymentInformation extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'hpg2_payment_information';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\PaymentInformation::class);
    }

    /**
     * Sets the order specific fields using information of the given order.
     *
     * @param Order $order
     *
     * @return void
     */
    public function setOrder(Order $order)
    {
        $this->setOrderId($order->getId());
        $this->setOrderIncrementId($order->getIncrementId());
        $this->setStoreId($order->getStoreId());
    }

    /**
     * Sets the Heidelpay transaction.
     *
     * @param AbstractTransactionType $transaction
     *
     * @return void
     */
    public function setTransaction(AbstractTransactionType $transaction)
    {
        $this->setExternalId($transaction->getPayment()->getExternalId());
        $this->setPaymentId($transaction->getPaymentId());
        $this->setRedirectUrl($transaction->getRedirectUrl());
    }
}
