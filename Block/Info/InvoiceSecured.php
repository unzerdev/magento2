<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Customer Account Order Invoice Information Block
 *
 * @link  https://docs.unzer.com/
 */
class InvoiceSecured extends Invoice
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::info/invoice_secured.phtml';

    /**
     * @inheritDoc
     */
    public function toPdf(): string
    {
        $this->setTemplate('Unzer_PAPI::info/pdf/invoice_secured.phtml');
        return $this->toHtml();
    }

    /**
     * Get Customer Salutation
     *
     * @return string
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function getCustomerSalutation(): string
    {
        return $this->_getPayment()->getCustomer()->getSalutation();
    }

    /**
     * Get Customer BirthDate
     *
     * @return string|null
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function getCustomerBirthdate(): ?string
    {
        return $this->_getPayment()->getCustomer()->getBirthDate();
    }
}
