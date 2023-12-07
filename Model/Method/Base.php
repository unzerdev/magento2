<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Validators\PrivateKeyValidator;
use UnzerSDK\Validators\PublicKeyValidator;

/**
 * Abstract base payment method
 *
 * @link  https://docs.unzer.com/
 */
class Base extends Adapter
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $_scopeConfig;
    /**
     * @var Config
     */
    protected Config $_moduleConfig;

    /**
     * Base constructor.
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $moduleConfig
     * @param CommandPoolInterface|null $commandPool
     * @param ValidatorPoolInterface|null $validatorPool
     * @param CommandManagerInterface|null $commandExecutor
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        ScopeConfigInterface $scopeConfig,
        Config $moduleConfig,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );

        $this->_scopeConfig = $scopeConfig;
        $this->_moduleConfig = $moduleConfig;
    }

    /**
     * Returns the configuration for the checkout page.
     *
     * @return array
     */
    public function getFrontendConfig(): array
    {
        if (!$this->hasMethodValidOverrideKeys()) {
            return [];
        }

        return [
            'publicKey' => $this->getMethodOverridePublicKey((string)$this->getStore()),
        ];
    }

    /**
     * Returns additional payment information for the customer.
     *
     * @param Order $order
     * @return string
     */
    public function getAdditionalPaymentInformation(Order $order): string
    {
        return '';
    }

    /**
     * Returns whether a redirect is required when making a payment.
     *
     * @return bool
     */
    public function hasRedirect(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $moduleConfig = $this->_moduleConfig;
        if ($quote === null) {
            return parent::isAvailable($quote);
        }

        $storeCode = $quote->getStore()->getCode();
        $isPrivateKeyValid = PrivateKeyValidator::validate($moduleConfig->getPrivateKey($storeCode, $this));
        $isPublicKeyValid = PublicKeyValidator::validate($moduleConfig->getPublicKey($storeCode, $this));
        if (!$isPrivateKeyValid || !$isPublicKeyValid) {
            return false;
        }

        if ($quote->getIsVirtual() && $this->isSecured()) {
            return false;
        }

        $hasCompany = !empty($quote->getBillingAddress()->getCompany());

        if (!$hasCompany && $this->isB2bOnly()) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Returns whether the payment method is only available for B2B customers.
     *
     * @return bool
     */
    public function isB2bOnly(): bool
    {
        return false;
    }

    /**
     * Returns whether the payment method is only available for B2C customers.
     *
     * @return bool
     */
    public function isB2cOnly(): bool
    {
        return false;
    }

    /**
     * Returns whether the payment method is safe.
     *
     * @return bool
     */
    public function isSecured(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return __(parent::getTitle());
    }

    /**
     * Can be used with risk data
     *
     * @return bool
     */
    public function hasRiskData(): bool
    {
        return false;
    }

    /**
     * Has Method Valid Override Keys
     *
     * @param string|null $storeId
     * @return bool
     */
    public function hasMethodValidOverrideKeys(string $storeId = null): bool
    {
        if (!$this->getConfigData(Config::OVERRIDE_API_KEYS, $storeId)) {
            return false;
        }

        $isPrivateKeyValid = PrivateKeyValidator::validate($this->getConfigData(Config::KEY_PRIVATE_KEY, $storeId));
        $isPublicKeyValid = PublicKeyValidator::validate($this->getConfigData(Config::KEY_PUBLIC_KEY, $storeId));

        if (!($isPrivateKeyValid && $isPublicKeyValid)) {
            return false;
        }
        return true;
    }

    /**
     * Get Method Override Public Key
     *
     * @param string|null $storeId
     * @return string
     */
    public function getMethodOverridePublicKey(string $storeId = null): string
    {
        return (string)$this->getConfigData(Config::KEY_PUBLIC_KEY, $storeId);
    }

    /**
     * Get Method Override Private Key
     *
     * @param string|null $storeId
     * @return string
     */
    public function getMethodOverridePrivateKey(string $storeId = null): string
    {
        return (string)$this->getConfigData(Config::KEY_PRIVATE_KEY, $storeId);
    }

    /**
     * Get vault code of payment method, if defined
     *
     * @return string|null
     */
    public function getVaultCode(): ?string
    {
        return null;
    }

    /**
     * Check, if we have to create the vault entry on return to success page.
     *
     * Otherwise we create the vault entry during authorization/capture.
     *
     * @return bool
     */
    public function isCreateVaultTokenOnSuccess(): bool
    {
        return (bool)$this->getConfigData(Config::CREATE_VAULT_TOKEN_ON_SUCCESS);
    }
}
