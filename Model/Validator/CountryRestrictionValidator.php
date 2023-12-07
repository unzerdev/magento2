<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Validator;

use Exception;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Unzer\PAPI\Model\Config;
use Magento\Payment\Gateway\Validator\CountryValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Validator for per payment method country restrictions
 *
 * @link  https://docs.unzer.com/
 */
class CountryRestrictionValidator extends CountryValidator
{
    /**
     * @var Config
     */
    private Config $config;

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
     * Validate
     *
     * @param array $validationSubject
     * @return bool|ResultInterface
     * @throws Exception
     */
    public function validate(array $validationSubject)
    {
        /** @var string|null $countriesString */
        $countriesString = $this->config->getValue('country_restrictions', $validationSubject['storeId']);

        if (!empty($countriesString)) {
            /** @var string[] $countries */
            $countries = preg_split('/\s*,\s*/', $countriesString);

            if (!in_array($validationSubject['country'], $countries, true)) {
                return $this->createResult(false);
            }
        }

        return parent::validate($validationSubject);
    }
}
