<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Form;

use Magento\Framework\View\Element\Template;
use Magento\Payment\Block\Form;
use Unzer\PAPI\Block\System\Config\Form\Field\BirthDate;
use Unzer\PAPI\Block\System\Config\Form\Field\BirthDateFactory;
use Unzer\PAPI\Block\System\Config\Form\Field\Salutation;

class InvoiceSecured extends Form
{
    /**
     * @var Salutation
     */
    private $salutation;

    /**
     * @var BirthDateFactory
     */
    private $birthDateFactory;

    /**
     * @var BirthDate
     */
    private $birthDate;

    public function __construct(
        Template\Context $context,
        Salutation $salutation,
        BirthDateFactory $birthDateFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->salutation = $salutation;
        $this->birthDateFactory = $birthDateFactory;
    }

    public function getSalutationOptions(): array
    {
        return $this->salutation->toOptionArray();
    }

    public function getBirthDate(): BirthDate
    {
        if (is_null($this->birthDate)) {
            $this->birthDate = $this->birthDateFactory->create();
            $this->birthDate->setDate($this->getInfoData('birthDate'));
        }
        return $this->birthDate;
    }
}
