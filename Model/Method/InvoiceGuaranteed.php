<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Model\Config;
use Magento\Quote\Api\Data\CartInterface;

class InvoiceGuaranteed extends Invoice
{
    protected $_code = Config::METHOD_INVOICE_GUARANTEED;

    protected $_infoBlockType = \Heidelpay\Gateway2\Block\Info\InvoiceGuaranteed::class;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @inheritDoc
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if ($quote !== null && !empty($quote->getBillingAddress()->getCompany())) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}
