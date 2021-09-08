<?php

namespace Unzer\PAPI\Model\Validator;

use Unzer\PAPI\Model\Config;
use Magento\Payment\Gateway\Validator\CountryValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Validator for per payment method country restrictions
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
 *
 * @author Justin Nuß
 *
 * @package  unzerdev/magento2
 */
class CountryRestrictionValidator extends CountryValidator
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param Config $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Config $config
    ) {
        parent::__construct($resultFactory, $config);

        $this->config = $config;
    }

    /**
     * @param array $validationSubject
     * @return bool|\Magento\Payment\Gateway\Validator\ResultInterface
     * @throws \Exception
     */
    public function validate(array $validationSubject)
    {
        /** @var string|null $countriesString */
        $countriesString = $this->config->getValue('country_restrictions', $validationSubject['storeId']);

        if (!empty($countriesString)) {
            /** @var string[] $countries */
            $countries = preg_split('/\s*,\s*/', $countriesString);

            if (!in_array($validationSubject['country'], $countries)) {
                return $this->createResult(false);
            }
        }

        return parent::validate($validationSubject);
    }
}
