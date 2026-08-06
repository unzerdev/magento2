<?php

declare(strict_types=1);

namespace Unzer\PAPI\Model\Payment\Status\Processor;

use Magento\Sales\Api\Data\OrderInterface;
use UnzerSDK\Resources\Payment as PaymentResource;

/**
 * Strategy that translates an Unzer Payment resource state into the
 * matching Magento order/invoice state for one payment method.
 *
 * @link  https://docs.unzer.com/
 */
interface ProcessorInterface
{
    /**
     * Apply the Magento-side effects of the current Unzer payment state.
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     *
     * @return void
     */
    public function process(OrderInterface $order, PaymentResource $payment): void;
}
