<?php

declare(strict_types=1);

namespace Unzer\PAPI\Model\Payment\Status\Processor;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment as PaymentResource;

/**
 * Status processor for Unzer Prepayment (unzer_prepayment).
 *
 * Prepayment requires the order to stay in STATE_PENDING_PAYMENT when
 * the payment is only partly settled, so subsequent partial transfers
 * are still expected from the customer. Default processPartly would
 * promote it via the state resolver.
 *
 * @link  https://docs.unzer.com/
 */
class PrepaymentProcessor extends AbstractProcessor
{
    /**
     * @inheritDoc
     *
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     */
    protected function processPartly(OrderInterface $order, PaymentResource $payment): void
    {
        $this->_transactionSynchronizer->applyCancellationOnMagento($order, $payment);
        $this->_transactionSynchronizer->applyCaptureOnMagento($order, $payment);

        $this->_orderStateApplier->setOrderState($order, Order::STATE_PENDING_PAYMENT);
    }
}
