<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Vault\Type;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Unzer\PAPI\Model\Config;

/**
 * PayPal Token uiComponent Provider
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
class PaypalTokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private TokenUiComponentInterfaceFactory $componentFactory;

    /**
     * Constructor
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory
    ) {
        $this->componentFactory = $componentFactory;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     * @throws \JsonException
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        return $this->componentFactory->create(
            [
                'config' => [
                    'code' => Config::METHOD_PAYPAL_VAULT,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash()
                ],
                'name' => 'Unzer_PAPI/js/view/payment/method-renderer/paypal_vault'
            ]
        );
    }
}
