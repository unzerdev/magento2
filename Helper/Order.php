<?php

declare(strict_types=1);

namespace Unzer\PAPI\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Config as MagentoTaxConfig;
use Unzer\PAPI\Block\System\Config\Form\Field\BirthDateFactory;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Source\CreateThreatMetrixId;
use Unzer\PAPI\Model\Source\Customer as CustomerResource;
use UnzerSDK\Constants\BasketItemTypes;
use UnzerSDK\Constants\Salutations;
use UnzerSDK\Constants\ShippingTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\BasketFactory;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\EmbeddedResources\Address;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\EmbeddedResources\BasketItemFactory;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

/**
 * Helper for generating Unzer resources for new orders
 *
 * @link  https://docs.unzer.com/
 */
class Order
{
    public const GENDER_MALE = 1;
    public const GENDER_FEMALE = 2;

    /**
     * @var Config
     */
    private Config $_moduleConfig;

    /**
     * @var ModuleListInterface
     */
    private ModuleListInterface $_moduleList;

    /**
     * @var ProductMetadataInterface
     */
    private ProductMetadataInterface $_productMetadata;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var BasketFactory
     */
    private BasketFactory $basketFactory;

    /**
     * @var BasketItemFactory
     */
    private BasketItemFactory $basketItemFactory;

    /**
     * @var BirthDateFactory
     */
    private BirthDateFactory $birthDateFactory;

    /**
     * @var CreateThreatMetrixId
     */
    private CreateThreatMetrixId $createThreatMetrixId;

    /**
     * @var ResolverInterface
     */
    private ResolverInterface $localeResolver;

    /**
     * Constructor
     *
     * @param Config $moduleConfig
     * @param ModuleListInterface $moduleList
     * @param ProductMetadataInterface $productMetadata
     * @param ScopeConfigInterface $scopeConfig
     * @param BasketFactory $basketFactory
     * @param BasketItemFactory $basketItemFactory
     * @param BirthDateFactory $birthDateFactory
     * @param CreateThreatMetrixId $createThreatMetrixId
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $moduleConfig,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        ScopeConfigInterface $scopeConfig,
        BasketFactory $basketFactory,
        BasketItemFactory $basketItemFactory,
        BirthDateFactory $birthDateFactory,
        CreateThreatMetrixId $createThreatMetrixId,
        ResolverInterface $localeResolver
    ) {
        $this->_moduleConfig = $moduleConfig;
        $this->_moduleList = $moduleList;
        $this->_productMetadata = $productMetadata;
        $this->scopeConfig = $scopeConfig;
        $this->basketFactory = $basketFactory;
        $this->basketItemFactory = $basketItemFactory;
        $this->birthDateFactory = $birthDateFactory;
        $this->createThreatMetrixId = $createThreatMetrixId;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Returns a Basket for the given Order.
     *
     * @param OrderModel $order
     *
     * @return Basket
     */
    public function createBasketForOrder(OrderModel $order): Basket
    {
        $basket = $this->createBasket($order);
        $vatRate = 0;
        $basketTotal = 0;

        if ($order->getShippingAmount() > 0) {
            $shippingItem = $this->createShippingItem($order);
            $basket->addBasketItem($shippingItem);
            $basketTotal += $shippingItem->getAmountPerUnitGross();
        }

        foreach ($order->getAllVisibleItems() as $orderItem) {
            /** @var Item $orderItem */

            // getAllVisibleItems() only checks getParentItemId() but it's possible that there is a parent item set
            // without a parent item id.
            if ($orderItem->getParentItem() !== null) {
                continue;
            }

            $item = $this->createBasketItem($orderItem);
            $basket->addBasketItem($item);

            if ($orderItem->getTaxPercent() !== null) {
                $vatRate = (float)$orderItem->getTaxPercent();
            }

            $basketTotal += $item->getAmountPerUnitGross() * $item->getQuantity();
        }

        if (abs($order->getBaseDiscountAmount()) > 0) {
            $vaucherItem = $this->createVoucherItem($order, $vatRate);
            $basket->addBasketItem($vaucherItem);
            $basketTotal -= abs($vaucherItem->getAmountDiscountPerUnitGross());
        }

        $totalOrder = $order->getBaseGrandTotal();
        $difference = round($totalOrder - $basketTotal, 2);

        if (abs($difference) > 0) {
            $basket->addBasketItem(
                $this->createRoundingItem($difference)
            );
        }

        return $basket;
    }

    /**
     * Create Basket
     *
     * @param OrderModel $order
     *
     * @return Basket
     */
    protected function createBasket(OrderModel $order): Basket
    {
        $basket = $this->basketFactory->create();
        $basket->setTotalValueGross($order->getBaseGrandTotal());
        $basket->setCurrencyCode($order->getBaseCurrencyCode());
        $basket->setOrderId($order->getIncrementId());

        return $basket;
    }

    /**
     * Create Shipping Item
     *
     * @param OrderModel $order
     *
     * @return BasketItem
     */
    protected function createShippingItem(OrderModel $order): BasketItem
    {
        /** @var BasketItem $basketItem */
        $basketItem = $this->basketItemFactory->create();
        $basketItem->setAmountPerUnitGross($order->getBaseShippingInclTax());
        $basketItem->setTitle('Shipment');
        $basketItem->setType(BasketItemTypes::SHIPMENT);

        return $basketItem;
    }

    /**
     * Create Basket Item
     *
     * @param Item $orderItem
     *
     * @return BasketItem
     */
    protected function createBasketItem(Item $orderItem): BasketItem
    {
        $basketItem = $this->basketItemFactory->create();
        $basketItem->setAmountPerUnitGross($orderItem->getBasePriceInclTax());
        $basketItem->setVat((float)$orderItem->getTaxPercent());
        $basketItem->setQuantity((int)$orderItem->getQtyOrdered());
        $basketItem->setTitle($orderItem->getName());
        $basketItem->setType($orderItem->getIsVirtual() ? BasketItemTypes::DIGITAL : BasketItemTypes::GOODS);

        return $basketItem;
    }


    private function createRoundingItem(float $amount): BasketItem
    {
        $item = new BasketItem();
        $item->setType(BasketItemTypes::VOUCHER);
        $item->setTitle('Rounding Adjustment');
        $item->setQuantity(1);
        $item->setVat(0.0);

        if ($amount < 0) {
            $item->setAmountDiscountPerUnitGross(abs($amount));
        }
        if ($amount > 0) {
            $item->setAmountPerUnitGross($amount);
        }

        return $item;
    }

    /**
     * Create Voucher Item
     *
     * @param OrderModel $order
     * @param float $vatRate
     *
     * @return BasketItem
     */
    protected function createVoucherItem(OrderModel $order, float $vatRate): BasketItem
    {
        $discount = $order->getBaseDiscountAmount();
        $storeId = $order->getStoreId();

        $taxAfterDiscount = $this->scopeConfig->getValue(
            MagentoTaxConfig::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $pricesIncludeTax = $this->scopeConfig->getValue(
            MagentoTaxConfig::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($taxAfterDiscount && !$pricesIncludeTax) {
            $discount *= (1 + $vatRate / 100);
        }

        $basketVoucherItemDiscountAmount = $this->basketItemFactory->create();
        $basketVoucherItemDiscountAmount->setAmountDiscountPerUnitGross(
            abs(round($discount, 2, PHP_ROUND_HALF_DOWN)
            )
        );
        $basketVoucherItemDiscountAmount->setVat($vatRate);
        $basketVoucherItemDiscountAmount->setAmountPerUnitGross(0);
        $basketVoucherItemDiscountAmount->setQuantity(1);
        $basketVoucherItemDiscountAmount->setTitle('Discount');
        $basketVoucherItemDiscountAmount->setType(BasketItemTypes::VOUCHER);

        return $basketVoucherItemDiscountAmount;
    }

    /**
     * Returns metadata for the given order.
     *
     * @param OrderModel $order
     *
     * @return Metadata
     */
    public function createMetadataForOrder(OrderModel $order): Metadata
    {
        $metaData = new Metadata();

        $metaData->setShopType('Magento 2')
            ->setShopVersion($this->_productMetadata->getVersion())
            ->addMetadata('pluginType', 'unzerdev/magento2')
            ->addMetadata('pluginVersion', $this->_moduleList->getOne('Unzer_PAPI')['setup_version']);

        return $metaData;
    }

    /**
     * Returns a new or updated Unzer Customer resource for the given quote.
     *
     * @param Quote $quote
     * @param string $email
     * @param bool $createResource
     *
     * @return Customer|null
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function createCustomerFromQuote(Quote $quote, string $email, bool $createResource = false): ?Customer
    {
        // A virtual quote does not have any customer data other than E-Mail so we can't create a customer object.
        if ($quote->isVirtual()) {
            return null;
        }

        $billingAddress = $quote->getBillingAddress();

        $customer = (new CustomerResource())
            ->setFirstname($billingAddress->getFirstname())
            ->setLastname($billingAddress->getLastname());

        $customer->setSalutation($this->getSalutationFromQuote($quote));
        $customer->setEmail($email);
        $customer->setPhone($billingAddress->getTelephone());
        $customer->setBirthDate($quote->getCustomer()->getDob());

        $threatMetrixId = $this->createThreatMetrixId->execute($quote);
        if ($threatMetrixId !== null) {
            $customer->setThreatMetrixId($threatMetrixId);
        }

        $company = $billingAddress->getCompany();
        if (!empty($company)) {
            $customer->setCompany($company);
        }
        $shippingAddress = $quote->getShippingAddress();
        $shippingType = $this->getShippingType($billingAddress, $shippingAddress);

        $this->updateGatewayAddressFromMagento($customer->getBillingAddress(), $billingAddress);
        $this->updateGatewayAddressFromMagento($customer->getShippingAddress(), $shippingAddress, $shippingType);

        $methodInstance = $quote->getPayment()->getMethod() ? $quote->getPayment()->getMethodInstance() : null;
        $client = $this->_moduleConfig->getUnzerClient($quote->getStore()->getCode(), $methodInstance);

        return $createResource ? $client->createCustomer($customer) : $customer;
    }

    /**
     * Returns a new or updated Unzer Customer resource for the given quote.
     *
     * @param OrderModel $order
     * @param string $email
     * @param bool $createResource
     *
     * @return Customer|null
     * @throws UnzerApiException|LocalizedException
     */
    public function createCustomerFromOrder(OrderModel $order, string $email, bool $createResource = false): ?Customer
    {
        $client = $this->_moduleConfig->getUnzerClient(
            $order->getStore()->getCode(),
            $order->getPayment()->getMethodInstance()
        );

        $billingAddress = $order->getBillingAddress();
        $customer = new Customer();
        $currentLocale = $this->localeResolver->getLocale();
        $languageCode = strtok($currentLocale, '_-');
        if($languageCode) {
            $customer->setLanguage($languageCode);
        }

        if ($billingAddress !== null) {
            $customer->setFirstname($billingAddress->getFirstname())
                ->setLastname($billingAddress->getLastname());

            $customer->setPhone($billingAddress->getTelephone());

            $company = $billingAddress->getCompany();
            if (!empty($company)) {
                $customer->setCompany($company);
            }

            $gender = $order->getCustomerGender();
            if ($gender) {
                $customer->setSalutation($this->getSalutationFromGender($gender));
            } else {
                $customer->setSalutation($this->getSalutationFromPayment($order->getPayment()));
            }
            $birthDate = $this->getBirthdateFromPayment($order->getPayment());
            if ($birthDate) {
                $customer->setBirthDate($birthDate);
            }
            $customer->setEmail($email);

            $this->updateGatewayAddressFromMagento($customer->getBillingAddress(), $billingAddress);
        }

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $shippingType = $this->getShippingType($billingAddress, $shippingAddress);
            $this->updateGatewayAddressFromMagento($customer->getShippingAddress(), $shippingAddress, $shippingType);
        }

        return $createResource ? $client->createCustomer($customer) : $customer;
    }

    /**
     * Returns a new Unzer Payment resource. Only used in adminhtml!
     *
     * @param OrderModel $order
     *
     * @return BasePaymentType|null
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function createPaymentFromOrder(OrderModel $order): ?BasePaymentType
    {
        $method = $order->getPayment()->getMethodInstance();
        if (!$method instanceof MethodInterface) {
            return null;
        }

        return $this->_moduleConfig
            ->getUnzerClient($order->getStore()->getCode(), $method)
            ->createPaymentType(
                $method->createPaymentType()
            );
    }

    /**
     * Updates an Unzer address from an address in Magento.
     *
     * @param Address $gatewayAddress
     * @param Quote\Address|OrderAddressInterface $magentoAddress
     * @param string $shippingType
     */
    private function updateGatewayAddressFromMagento(
        Address $gatewayAddress,
        $magentoAddress,
        string $shippingType = ShippingTypes::EQUALS_BILLING
    ): void {
        $street = $this->convertStreetLinesToString($magentoAddress->getStreet());

        $gatewayAddress->setName($magentoAddress->getName());
        $gatewayAddress->setCity($magentoAddress->getCity());
        $gatewayAddress->setCountry($magentoAddress->getCountryId());
        $gatewayAddress->setStreet($street);
        $gatewayAddress->setZip($magentoAddress->getPostcode());
        if ($magentoAddress->getAddressType() === Quote\Address::ADDRESS_TYPE_SHIPPING) {
            $gatewayAddress->setShippingType($shippingType);
        }
    }

    /**
     * Get Shipping Type
     *
     * @param $billingAddress
     * @param $shippingAddress
     *
     * @return string
     */
    public function getShippingType($billingAddress, $shippingAddress): string
    {
        $billingStreet = $this->convertStreetLinesToString($billingAddress->getStreet());
        $shippingStreet = $this->convertStreetLinesToString($shippingAddress->getStreet());

        if ($billingAddress->getName() !== $shippingAddress->getName()) {
            return ShippingTypes::DIFFERENT_ADDRESS;
        }
        if ($billingAddress->getCity() !== $shippingAddress->getCity()) {
            return ShippingTypes::DIFFERENT_ADDRESS;
        }
        if ($billingAddress->getCountryId() !== $shippingAddress->getCountryId()) {
            return ShippingTypes::DIFFERENT_ADDRESS;
        }
        if ($billingStreet !== $shippingStreet) {
            return ShippingTypes::DIFFERENT_ADDRESS;
        }
        if ($billingAddress->getZip() !== $shippingAddress->getZip()) {
            return ShippingTypes::DIFFERENT_ADDRESS;
        }

        return ShippingTypes::EQUALS_BILLING;
    }

    /**
     * Convert Street Lines To String
     *
     * @param array $streetLines
     *
     * @return string
     */
    private function convertStreetLinesToString(array $streetLines): string
    {
        $streetLines = array_map('trim', $streetLines);
        $streetLines = array_unique($streetLines);
        return implode(' ', $streetLines);
    }

    /**
     * Update Gateway Customer From Order
     *
     * @param OrderModel $order
     * @param Customer $gatewayCustomer
     *
     * @throws UnzerApiException|LocalizedException
     */
    public function updateGatewayCustomerFromOrder(OrderModel $order, Customer $gatewayCustomer): void
    {
        $billingAddress = $order->getBillingAddress();

        $gatewayCustomer->setFirstname($billingAddress->getFirstname());
        $gatewayCustomer->setLastname($billingAddress->getLastname());

        $gatewayCustomer->setCompany($billingAddress->getCompany());
        $gatewayCustomer->setEmail($billingAddress->getEmail());

        $this->updateGatewayAddressFromMagento(
            $gatewayCustomer->getBillingAddress(),
            $billingAddress
        );

        $shippingAddress = $order->getShippingAddress();
        if (null !== $shippingAddress) {
            $shippingType = $this->getShippingType($billingAddress, $shippingAddress);
            $this->updateGatewayAddressFromMagento(
                $gatewayCustomer->getShippingAddress(),
                $shippingAddress,
                $shippingType
            );
        }

        $client = $this->_moduleConfig->getUnzerClient(
            $order->getStore()->getCode(),
            $order->getPayment()->getMethodInstance()
        );
        $client->updateCustomer($gatewayCustomer);
    }

    /**
     * Validates that the given Order and Customer have matching data.
     *
     * @param OrderModel $order
     * @param Customer $gatewayCustomer
     *
     * @return bool
     */
    public function validateGatewayCustomerAgainstOrder(OrderModel $order, Customer $gatewayCustomer): bool
    {
        $nameValid = $gatewayCustomer->getFirstname() === $order->getBillingAddress()->getFirstname()
            && $gatewayCustomer->getLastname() === $order->getBillingAddress()->getLastname();

        // Magento's getCompany() always returns a string, but the Unzer Customer Address does not, so we must make
        // sure that both have the same type.
        $companyValid = ($order->getBillingAddress()->getCompany() ?? '') === ($gatewayCustomer->getCompany() ?? '');
        $emailValid = $order->getCustomerEmail() === $gatewayCustomer->getEmail();

        $billingAddressValid = $this->validateGatewayAddressAgainstOrderAddress(
            $gatewayCustomer->getBillingAddress(),
            $order->getBillingAddress()
        );

        $shippingAddress = $order->getShippingAddress();
        $shippingAddressValid = true;
        if ($shippingAddress !== null) {
            $shippingAddressValid = $this->validateGatewayAddressAgainstOrderAddress(
                $gatewayCustomer->getShippingAddress(),
                $shippingAddress
            );
        }

        return $nameValid && $companyValid && $billingAddressValid && $shippingAddressValid && $emailValid;
    }

    /**
     * Validate Gateway Address Against Order Address
     *
     * @param Address $gatewayAddress
     * @param OrderAddressInterface $magentoAddress
     *
     * @return bool
     */
    private function validateGatewayAddressAgainstOrderAddress(
        Address $gatewayAddress,
        OrderAddressInterface $magentoAddress
    ): bool {
        $street = $this->convertStreetLinesToString($magentoAddress->getStreet());

        return $gatewayAddress->getCity() === $magentoAddress->getCity()
            && $gatewayAddress->getCountry() === $magentoAddress->getCountryId()
            && $gatewayAddress->getStreet() === $street
            && $gatewayAddress->getZip() === $magentoAddress->getPostcode();
    }

    /**
     * Returns the Unzer salutation constant depending on the gender of the customer.
     * Male -> 1 -> mr
     * Female -> 2 -> mrs
     * Default -> unknown
     *
     * @param Quote $quote
     *
     * @return string
     */
    protected function getSalutationFromQuote(Quote $quote): string
    {
        return $this->getSalutationFromGender($quote->getCustomer()->getGender());
    }

    /**
     * Get Salutation From Gender
     *
     * @param float|int $gender
     *
     * @return string
     */
    protected function getSalutationFromGender($gender): string
    {
        switch ($gender) {
            case self::GENDER_MALE:
                $salutation = Salutations::MR;
                break;
            case self::GENDER_FEMALE:
                $salutation = Salutations::MRS;
                break;
            default:
                $salutation = Salutations::UNKNOWN;
        }
        return $salutation;
    }

    /**
     * Get Salutation From Payment
     *
     * @param InfoInterface $payment
     *
     * @return string|null
     */
    protected function getSalutationFromPayment(InfoInterface $payment): ?string
    {
        return $payment->getAdditionalInformation('salutation');
    }

    /**
     * Get Birthdate from Payment
     *
     * @param InfoInterface $payment
     *
     * @return string|null
     */
    protected function getBirthdateFromPayment(InfoInterface $payment): ?string
    {
        $birthDate = $this->birthDateFactory->create();
        $birthDate->setDate($payment->getAdditionalInformation('birthDate'));

        $date = $birthDate->getDate();
        if ($date === null) {
            return null;
        }
        return $date->format('Y-m-d');
    }
}
