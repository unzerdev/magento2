<?php

namespace Heidelpay\Gateway2\Model\Command;

use Heidelpay\Gateway2\Model\Method\Observer\BaseDataAssignObserver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

class Authorize extends AbstractCommand
{
    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var float $amount */
        $amount = $commandSubject['amount'];

        /** @var Order $order */
        $order = $payment->getOrder();

        /** @var string|null $customerId */
        $customerId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_CUSTOMER_ID);

        /** @var string $resourceId */
        $resourceId = $payment->getAdditionalInformation(BaseDataAssignObserver::KEY_RESOURCE_ID);

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

        /** @var OrderPayment $payment */
        $payment->setLastTransId($authorization->getPaymentId());
        $payment->setTransactionId($authorization->getPaymentId());

        return null;
    }
}
