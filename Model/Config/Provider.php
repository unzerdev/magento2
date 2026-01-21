<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Config;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Vault\Model\VaultPaymentInterface;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base as MethodBase;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use UnzerSDK\Resources\Customer;

/**
 * JavaScript configuration provider
 *
 * @link  https://docs.unzer.com/
 */
class Provider implements ConfigProviderInterface
{
    /**
     * @var array
     */
    protected array $_methodCodes = [
        Config::METHOD_PAYLATER_INVOICE,
        Config::METHOD_PAYLATER_INVOICE_B2B,
        Config::METHOD_PAYLATER_INSTALLMENT,
        Config::METHOD_PAYLATER_DIRECT_DEBIT,
        Config::METHOD_OPEN_BANKING,
        Config::METHOD_CARDS,
        Config::METHOD_APPLEPAYV2,
        Config::METHOD_GOOGLEPAY,
        Config::METHOD_WERO,
        Config::METHOD_KLARNA,
        Config::METHOD_EPS,
        Config::METHOD_IDEAL,
        Config::METHOD_PRZELEWY24,
        Config::METHOD_TWINT,
        Config::METHOD_DIRECT_DEBIT,
        Config::METHOD_PREPAYMENT,
        Config::METHOD_BANCONTACT,
        Config::METHOD_PAYPAL,
        Config::METHOD_ALIPAY,
        Config::METHOD_WECHATPAY,
    ];

    /**
     * @var Config
     */
    private Config $_moduleConfig;

    /**
     * @var PaymentHelper
     */
    private PaymentHelper $_paymentHelper;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Session
     */
    private Session $checkoutSession;


    /**
     * Provider constructor.
     * @param Config $moduleConfig
     * @param PaymentHelper $paymentHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     */
    public function __construct(
        Config $moduleConfig,
        PaymentHelper $paymentHelper,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession
    ) {
        $this->_moduleConfig = $moduleConfig;
        $this->_paymentHelper = $paymentHelper;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        /** @var Customer $baseCustomer */
        $baseCustomer = $quote ? $this->fetchUnzerCustomer($quote) : null;

        $methodConfigs = [
            Config::METHOD_BASE => [
                'publicKey' => $this->_moduleConfig->getPublicKey(),
                'locale' => str_replace('_', '-', $this->scopeConfig->getValue('general/locale/code', 'store'))
            ],
        ];

        foreach ($this->_methodCodes as $methodCode) {
            /** @var MethodBase $model */
            $model = $this->_paymentHelper->getMethodInstance($methodCode);
            if ($model instanceof VaultPaymentInterface || !$model->isAvailable()) {
                continue;
            }

            $methodConfig = $model->getFrontendConfig();

            if (!$model->hasMethodValidOverrideKeys()) {
                if ($baseCustomer) {
                    $methodConfig['unzerCustomerId'] = $baseCustomer->getId();
                }

                $methodConfigs[$model->getCode()] = $methodConfig;
                continue;
            }

            $overrideCustomer = $this->fetchUnzerCustomer($quote, $model);

            if ($overrideCustomer) {
                $methodConfig['unzerCustomerId'] = $overrideCustomer->getId();
            }

            $methodConfigs[$model->getCode()] = $methodConfig;
        }

        return [
            'payment' => array_filter($methodConfigs),
        ];
    }

    /**
     * @param Quote $quote
     * @param MethodBase|null $method
     *
     * @return Customer|null
     */
    private function fetchUnzerCustomer(Quote $quote, ?MethodBase $method = null): ?Customer
    {
        if ($quote->getCustomerIsGuest() || !$quote->getCustomerId()) {
            return null;
        }

        try {
            $client = $this->_moduleConfig->getUnzerClient(
                $quote->getStore()->getCode(),
                $method
            );

            return $client->fetchCustomerByExtCustomerId((string)$quote->getCustomerId());
        } catch (\Exception $e) {
            return null;
        }
    }
}
