<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Info;

/**
 * Customer Account Order Invoice Information Block
 *
 * @link  https://docs.unzer.com/
 */
class InvoiceSecuredB2b extends InvoiceSecured
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::info/invoice_secured_b2b.phtml';

    /**
     * @inheritDoc
     */
    public function toPdf(): string
    {
        $this->setTemplate('Unzer_PAPI::info/pdf/invoice_secured_b2b.phtml');
        return $this->toHtml();
    }
}
