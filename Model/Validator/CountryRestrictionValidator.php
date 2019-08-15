<?php

namespace Heidelpay\Gateway2\Model\Validator;

use Heidelpay\Gateway2\Model\Config;
use Magento\Payment\Gateway\Validator\CountryValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

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
        $countriesString = $this->config->getValue('country_restrictions');

        if (!empty($countriesString)) {
            /** @var string[] $countries */
            $countries = preg_split('\s*,\s*', $this->config->getValue('country_restrictions'));

            if (!in_array($validationSubject['country'], $countries)) {
                return $this->createResult(false);
            }
        }

        return parent::validate($validationSubject);
    }
}
