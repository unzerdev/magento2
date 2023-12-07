<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Plugin\AdminOrder;

use Exception;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\AdminOrder\EmailSender;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Psr\Log\LoggerInterface as Logger;

/**
 * @link  https://docs.unzer.com/
 */
class EmailSenderPlugin
{
    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var OrderSender
     */
    protected OrderSender $orderSender;

    /**
     * @var InvoiceSender
     */
    private InvoiceSender $invoiceSender;

    /**
     * Constructor
     *
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

    /**
     * Around Send
     *
     * @param EmailSender $emailSender
     * @param callable $proceed
     * @param Order $order
     * @return bool
     * @throws Exception
     */
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
     * @throws MailException|Exception
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
