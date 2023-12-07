<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;

/**
 * Handler for checking if payments can be voided
 *
 * @link  https://docs.unzer.com/
 */
class CanVoidHandler implements ValueHandlerInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function handle(array $subject, $storeId = null)
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $subject['payment']->getPayment();
        if (!$payment instanceof Payment) {
            return false;
        }

        if (!$this->canVoid($payment)) {
            return false;
        }

        return (float)$payment->getBaseAmountAuthorized() > (float)$payment->getBaseAmountPaid();
    }

    /**
     * Can Void
     *
     * @param Payment $payment
     * @return bool
     * @throws LocalizedException
     */
    private function canVoid(Payment $payment): bool
    {
        $storeId = $payment->getOrder()->getStoreId();

        $path = 'payment/' . $payment->getMethodInstance()->getCode() . '/' . 'can_void';
        return (bool)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
