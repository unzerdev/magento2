<?php

declare(strict_types=1);

namespace Unzer\PAPI\Model\Payment\Status;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;

/**
 * Resolves and persists order state/status transitions and sends the
 * order/invoice confirmation emails when appropriate.
 *
 * Extracted from Helper\Payment so both the Helper (called directly by
 * Controller\Payment\Redirect) and the status Processor pool can share
 * the same transition logic without forming a DI cycle.
 *
 * @link  https://docs.unzer.com/
 */
class OrderStateApplier
{
    /**
     * @var OrderRepository
     */
    private OrderRepository $orderRepository;

    /**
     * @var Order\OrderStateResolverInterface
     */
    private Order\OrderStateResolverInterface $orderStateResolver;

    /**
     * @var Order\StatusResolver
     */
    private Order\StatusResolver $orderStatusResolver;

    /**
     * @var OrderSender
     */
    private OrderSender $orderSender;

    /**
     * @var InvoiceSender
     */
    private InvoiceSender $invoiceSender;

    /**
     * @param OrderRepository $orderRepository
     * @param Order\OrderStateResolverInterface $orderStateResolver
     * @param Order\StatusResolver $orderStatusResolver
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     */
    public function __construct(
        OrderRepository $orderRepository,
        Order\OrderStateResolverInterface $orderStateResolver,
        Order\StatusResolver $orderStatusResolver,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStateResolver = $orderStateResolver;
        $this->orderStatusResolver = $orderStatusResolver;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
    }

    /**
     * Apply state/status to the order, persist if changed, and send the
     * confirmation emails unless the state is one Magento treats as not
     * yet customer-facing.
     *
     * @param OrderInterface $order
     * @param string|null $state
     * @param string|null $status
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function setOrderState(OrderInterface $order, ?string $state = null, ?string $status = null): void
    {
        if ($state === null) {
            $state = $this->orderStateResolver->getStateForOrder($order, [
                Order\OrderStateResolverInterface::IN_PROGRESS,
            ]);
        }

        if ($status === null) {
            $status = $this->orderStatusResolver->getOrderStatusByState($order, $state);
        }

        $order->setState($state);
        $order->setStatus($status);

        if ($order->hasDataChanges()) {
            $this->orderRepository->save($order);
        }

        if ($order->getEmailSent()) {
            return;
        }

        if (in_array($state, [Order::STATE_NEW, Order::STATE_CANCELED, Order::STATE_PENDING_PAYMENT], true)) {
            return;
        }

        $this->sendEmails($order);
    }

    /**
     * Send the order confirmation email plus an email for every invoice
     * that hasn't been emailed yet. Only fires for methods that use the
     * Order command (canOrder()), matching the pre-extraction behavior.
     *
     * @param OrderInterface $order
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    private function sendEmails(OrderInterface $order): void
    {
        $payment = $order->getPayment();
        if (!$payment instanceof OrderPaymentInterface || !$payment->getMethodInstance()->canOrder()) {
            return;
        }

        $this->orderSender->send($order);

        foreach ($order->getInvoiceCollection() as $invoice) {
            /** @var Order\Invoice $invoice */
            if (!$invoice->getEmailSent()) {
                $this->invoiceSender->send($invoice);
            }
        }
    }
}
