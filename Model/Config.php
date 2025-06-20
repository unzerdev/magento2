<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Unzer\PAPI\Model\Logger\DebugHandler;
use Unzer\PAPI\Model\Method\OverrideApiCredentialInterface;
use UnzerSDK\Unzer;

/**
 * Global Module configuration and SDK provider
 *
 * @link  https://docs.unzer.com/
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const BASE_CONFIGURATION_PATH = 'payment/unzer/';

    public const OVERRIDE_API_KEYS = 'override_api_keys';
    public const KEY_PUBLIC_KEY = 'public_key';
    public const KEY_PRIVATE_KEY = 'private_key';
    public const KEY_LOGGING = 'logging';

    public const CREATE_VAULT_TOKEN_ON_SUCCESS = 'create_vault_token_on_success';

    public const METHOD_BASE = 'unzer';
    public const METHOD_CARDS = 'unzer_cards';

    public const METHOD_CARDS_VAULT = 'unzer_cards_vault';

    public const METHOD_DIRECT_DEBIT = 'unzer_direct_debit';
    public const METHOD_DIRECT_DEBIT_SECURED = 'unzer_direct_debit_secured';
    public const METHOD_EPS = 'unzer_eps';
    public const METHOD_GIROPAY = 'unzer_giropay';
    public const METHOD_BANK_TRANSFER = 'unzer_bank_transfer';
    public const METHOD_IDEAL = 'unzer_ideal';
    public const METHOD_INVOICE = 'unzer_invoice';
    public const METHOD_PAYLATER_INVOICE = 'unzer_paylater_invoice';
    public const METHOD_PAYLATER_INVOICE_B2B = 'unzer_paylater_invoice_b2b';
    public const METHOD_PAYLATER_INSTALLMENT = 'unzer_paylater_installment';
    public const METHOD_PAYLATER_DIRECT_DEBIT = 'unzer_paylater_direct_debit';
    public const METHOD_INVOICE_SECURED_B2B = 'unzer_invoice_secured_b2b';
    public const METHOD_INVOICE_SECURED = 'unzer_invoice_secured';
    public const METHOD_PAYPAL = 'unzer_paypal';
    public const METHOD_PAYPAL_VAULT = 'unzer_paypal_vault';
    public const METHOD_SOFORT = 'unzer_sofort';
    public const METHOD_ALIPAY = 'unzer_alipay';
    public const METHOD_WECHATPAY = 'unzer_wechatpay';
    public const METHOD_PRZELEWY24 = 'unzer_przelewy24';
    public const METHOD_BANCONTACT = 'unzer_bancontact';
    public const METHOD_PREPAYMENT = 'unzer_prepayment';
    public const METHOD_APPLEPAY = 'unzer_applepay';
    public const METHOD_APPLEPAYV2 = 'unzer_applepayv2';
    public const METHOD_GOOGLEPAY = 'unzer_googlepay';
    public const METHOD_TWINT = 'unzer_twint';
    public const METHOD_OPEN_BANKING = 'unzer_open_banking';

    /**
     * @var DebugHandler
     */
    private DebugHandler $_debugHandler;

    /**
     * @var Resolver
     */
    private Resolver $_localeResolver;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $_scopeConfig;

    /**
     * @var Request
     */
    private Request $_request;

    /**
     * @var CcConfig
     */
    private CcConfig $ccConfig;

    /**
     * Config constructor.
     *
     * @param Resolver $localeResolver
     * @param ScopeConfigInterface $scopeConfig
     * @param DebugHandler $debugHandler
     * @param CcConfig $ccConfig
     * @param Request $request
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        Resolver $localeResolver,
        ScopeConfigInterface $scopeConfig,
        DebugHandler $debugHandler,
        CcConfig $ccConfig,
        Request $request,
        ?string $methodCode = null,
        string $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);

        $this->_debugHandler = $debugHandler;
        $this->_localeResolver = $localeResolver;
        $this->_scopeConfig = $scopeConfig;
        $this->ccConfig = $ccConfig;
        $this->_request = $request;
    }

    /**
     * Is debug mode
     *
     * @param string|null $storeId
     * @return bool
     */
    private function isDebugMode(?string $storeId = null): bool
    {
        return $this->_scopeConfig->isSetFlag(
            self::BASE_CONFIGURATION_PATH . self::KEY_LOGGING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the public key.
     *
     * @param string|null $storeId
     * @param MethodInterface|null $paymentMethodInstance
     * @return string|null
     */
    public function getPublicKey(?string $storeId = null, ?MethodInterface $paymentMethodInstance = null): ?string
    {
        if ($paymentMethodInstance instanceof OverrideApiCredentialInterface
            && $paymentMethodInstance->hasMethodValidOverrideKeys($storeId)
        ) {
            return $paymentMethodInstance->getMethodOverridePublicKey($storeId);
        }

        return $this->_scopeConfig->getValue(
            self::BASE_CONFIGURATION_PATH . self::KEY_PUBLIC_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the private key.
     *
     * @param string|null $storeId
     * @param MethodInterface|null $paymentMethodInstance
     * @return string|null
     */
    public function getPrivateKey(?string $storeId = null, ?MethodInterface $paymentMethodInstance = null): ?string
    {
        if ($paymentMethodInstance instanceof OverrideApiCredentialInterface
            && $paymentMethodInstance->hasMethodValidOverrideKeys($storeId)
        ) {
            return $paymentMethodInstance->getMethodOverridePrivateKey($storeId);
        }

        return $this->_scopeConfig->getValue(
            self::BASE_CONFIGURATION_PATH . self::KEY_PRIVATE_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns an API client using the configured private key.
     *
     * @param string|null $storeId
     * @param MethodInterface|null $paymentMethodInstance
     * @return Unzer
     */
    public function getUnzerClient(?string $storeId = null, ?MethodInterface $paymentMethodInstance = null): Unzer
    {
        $client = new Unzer(
            $this->getPrivateKey($storeId, $paymentMethodInstance),
            $this->_localeResolver->getLocale()
        );

        $clientsIpAddress = $this->_request->getClientIp();

        if (filter_var($clientsIpAddress, FILTER_VALIDATE_IP)) {
            $client->setClientIp($clientsIpAddress);
        }

        $client->setDebugMode($this->isDebugMode($storeId));
        $client->setDebugHandler($this->_debugHandler);

        return $client;
    }

    /**
     * Get PayPal icon
     *
     * @return array
     */
    public function getPayPalIcon(): array
    {
        if (empty($this->icon)) {
            $asset = $this->ccConfig->createAsset('Magento_Paypal::images/paypal-logo.png');
            [$width, $height] = getimagesizefromstring($asset->getSourceFile());
            $this->icon = [
                'url' => $asset->getUrl(),
                'width' => $width,
                'height' => $height
            ];
        }

        return $this->icon;
    }
}
