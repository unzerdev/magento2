<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Customer;

use IntlDateFormatter;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\CcConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\System\Config\CreditCardBrand;

/**
 * Cards Renderer
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
class CardsRenderer extends AbstractCardRenderer
{
    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @var CreditCardBrand
     */
    private CreditCardBrand $creditCardBrand;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CcConfigProvider $iconsProvider
     * @param TimezoneInterface $timezoneInterface
     * @param CreditCardBrand $creditCardBrand
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CcConfigProvider $iconsProvider,
        TimezoneInterface $timezoneInterface,
        CreditCardBrand $creditCardBrand,
        array $data = []
    ) {
        parent::__construct($context, $iconsProvider, $data);
        $this->timezoneInterface = $timezoneInterface;
        $this->creditCardBrand = $creditCardBrand;
    }

    /**
     * Can render specified token
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token): bool
    {
        return $token->getPaymentMethodCode() === Config::METHOD_CARDS;
    }

    /**
     * Get Number Last 4 digits
     *
     * @return string
     */
    public function getNumberLast4Digits(): string
    {
        return $this->getTokenDetails()['maskedCC'];
    }

    /**
     * Get Expiration Date
     *
     * @return string
     */
    public function getExpDate(): string
    {
        return $this->timezoneInterface->formatDate(
            $this->getTokenDetails()['expirationDate'],
            IntlDateFormatter::LONG
        );
    }

    /**
     * Get Brand
     *
     * @return string
     */
    public function getBrand(): string
    {
        return $this->creditCardBrand->getBrandByType($this->getTokenDetails()['type']);
    }

    /**
     * Get Icon Url
     *
     * @return string
     */
    public function getIconUrl(): string
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['url'];
    }

    /**
     * Get Icon Height
     *
     * @return int
     */
    public function getIconHeight(): int
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    /**
     * Get Icon Width
     *
     * @return int
     */
    public function getIconWidth(): int
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['width'];
    }
}
