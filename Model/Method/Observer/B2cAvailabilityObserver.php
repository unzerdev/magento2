<?php

namespace Heidelpay\Gateway2\Model\Method\Observer;

use Heidelpay\Gateway2\Model\Method\DirectDebitGuaranteed;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

class B2CAvailabilityObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var MethodInterface $methodInstance */
        $methodInstance = $observer->getEvent()->getData('method_instance');

        if (!$methodInstance instanceof DirectDebitGuaranteed) {
            return;
        }

        /** @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        $isAvailable = $quote === null || empty($quote->getBillingAddress()->getCompany());

        $resultObject = $observer->getEvent()->getData('result');
        $resultObject->setData('is_available', $isAvailable);
    }
}
