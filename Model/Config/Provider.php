<?php

namespace Heidelpay\Gateway2\Model\Config;

use Heidelpay\Gateway2\Model\Config;
use Heidelpay\Gateway2\Model\Method\Base as MethodBase;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;

class Provider implements ConfigProviderInterface
{
    protected $_methodCodes = [
        Config::METHOD_CREDITCARD,
        Config::METHOD_FLEXIPAY_DIRECT,
        Config::METHOD_IDEAL,
        Config::METHOD_PAYPAL,
        Config::METHOD_SOFORT,
    ];

    /**
     * @var Config
     */
    private $_moduleConfig;

    /**
     * @var PaymentHelper
     */
    private $_paymentHelper;

    /**
     * Provider constructor.
     * @param Config $moduleConfig
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(Config $moduleConfig, PaymentHelper $paymentHelper)
    {
        $this->_moduleConfig = $moduleConfig;
        $this->_paymentHelper = $paymentHelper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     *
     */
    public function getConfig()
    {
        $methodConfigs = [
            Config::METHOD_BASE => [
                'publicKey' => $this->_moduleConfig->getPublicKey(),
            ],
        ];

        foreach ($this->_methodCodes as $methodCode) {
            /** @var MethodBase $model */
            $model = $this->_paymentHelper->getMethodInstance($methodCode);

            if ($model->isAvailable()) {
                $methodConfigs[$model->getCode()] = $model->getFrontendConfig();
            }
        }

        return [
            'payment' => array_filter($methodConfigs),
        ];
    }
}
