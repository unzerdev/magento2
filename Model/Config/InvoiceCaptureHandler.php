<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Handler for checking if invoice payments can be captured.
 *
 * @link  https://docs.unzer.com/
 */
class InvoiceCaptureHandler implements ValueHandlerInterface
{
    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function handle(array $subject, $storeId = null)
    {
        /** @var Payment $payment */
        $payment = $subject['payment']->getPayment();

        $invoiceCount = (int) $payment
            ->getOrder()
            ->getInvoiceCollection()
            ->getSize();

        return $invoiceCount === 0;
    }
}
