<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Form;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Block\Form;
use Unzer\PAPI\Block\System\Config\Form\Field\BirthDate;
use Unzer\PAPI\Block\System\Config\Form\Field\BirthDateFactory;
use Unzer\PAPI\Block\System\Config\Form\Field\Salutation;

/**
 * Invoice Secured
 *
 * Copyright (C) 2021 - today Unzer GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.unzer.com/
 */
class InvoiceSecured extends Form
{
    /**
     * @var Salutation
     */
    private Salutation $salutation;

    /**
     * @var BirthDateFactory
     */
    private BirthDateFactory $birthDateFactory;

    /**
     * @var ?BirthDate
     */
    private ?BirthDate $birthDate;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Salutation $salutation
     * @param BirthDateFactory $birthDateFactory
     * @param array $data
     */
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

    /**
     * Get Salutation Options
     *
     * @return array
     */
    public function getSalutationOptions(): array
    {
        return $this->salutation->toOptionArray();
    }

    /**
     * Get BirthDate
     *
     * @return BirthDate
     * @throws LocalizedException
     */
    public function getBirthDate(): BirthDate
    {
        if ($this->birthDate === null) {
            $this->birthDate = $this->birthDateFactory->create();
            $this->birthDate->setDate($this->getInfoData('birthDate'));
        }
        return $this->birthDate;
    }

    /**
     * Get Info Data
     *
     * @param string $field
     * @return mixed
     * @throws LocalizedException
     */
    public function getInfoData($field)
    {
        return $this->getMethod()->getInfoInstance()->getData($field);
    }
}
