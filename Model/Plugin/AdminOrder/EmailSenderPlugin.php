<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Plugin\AdminOrder;

use Magento\Framework\Exception\MailException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\AdminOrder\EmailSender;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Psr\Log\LoggerInterface as Logger;

class EmailSenderPlugin
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     */
    public function __construct(
        ManagerInterface $messageManager,
        Logger $logger,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender
    ) {
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
    }

    public function aroundSend(EmailSender $emailSender, callable $proceed, Order $order): bool
    {
        $return = $proceed($order);

        if ($return === false) {
            return false;
        }

        try {
            $this->sendInvoiceEmail($order);
        } catch (MailException $exception) {
            $this->logger->critical($exception);
            $this->messageManager->addWarningMessage(
                __('You did not email your customer. Please check your email settings.')
            );
            return false;
        }

        return true;
    }

    /**
     * Send email about invoice paying
     *
     * @param Order $order
     * @throws MailException
     */
    private function sendInvoiceEmail(Order $order): void
    {
        foreach ($order->getInvoiceCollection()->getItems() as $invoice) {
            /** @var Invoice $invoice */
            if ($invoice->getState() === Invoice::STATE_OPEN) {
                $this->invoiceSender->send($invoice);
            }
        }
    }
}
