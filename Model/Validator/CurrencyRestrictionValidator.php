<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Validator;

use Exception;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Unzer\PAPI\Model\Config;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Validator for per payment method currency restrictions
 *
 * @link  https://docs.unzer.com/
 */
class CurrencyRestrictionValidator extends AbstractValidator
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
        parent::__construct($resultFactory);

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
        $isValid = true;

        /** @var string|null $currencyString */
        $currencyString = $this->config->getValue('currency_restrictions', $validationSubject['storeId']);

        if (!empty($currencyString)) {
            /** @var string[] $currencies */
            $currencies = preg_split('/\s*,\s*/', $currencyString);

            if (!in_array($validationSubject['currency'], $currencies)) {
                $isValid =  false;
            }
        }

        return $this->createResult($isValid);
    }
}
