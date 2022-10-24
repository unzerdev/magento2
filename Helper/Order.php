<?php

namespace Unzer\PAPI\Helper;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order as OrderModel;
use Unzer\PAPI\Block\System\Config\Form\Field\BirthDateFactory;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Constants\BasketItemTypes;
use UnzerSDK\Constants\Salutations;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\BasketFactory;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\CustomerFactory;
use UnzerSDK\Resources\EmbeddedResources;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Resources\EmbeddedResources\BasketItemFactory;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

/**
 * Helper for generating Unzer resources for new orders
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
class Order
{
    public const GENDER_MALE = 1;
    public const GENDER_FEMALE = 2;

    /**
     * @var Config
     */
    private $_moduleConfig;

    /**
     * @var ModuleListInterface
     */
    private $_moduleList;

    /**
     * @var ProductMetadataInterface
     */
    private $_productMetadata;

    /**
     * @var Basket
     */
    private $basketFactory;

    /**
     * @var BasketItemFactory
     */
    private $basketItemFactory;

    /**
     * @var BirthDateFactory
     */
    private $birthDateFactory;

    public function __construct(
        Config $moduleConfig,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        BasketFactory $basketFactory,
        BasketItemFactory $basketItemFactory,
        BirthDateFactory $birthDateFactory
    ) {
        $this->_moduleConfig = $moduleConfig;
        $this->_moduleList = $moduleList;
        $this->_productMetadata = $productMetadata;
        $this->basketFactory = $basketFactory;
        $this->basketItemFactory = $basketItemFactory;
        $this->birthDateFactory = $birthDateFactory;
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
        $transmitInCustomerCurrency = $this->_moduleConfig->getTransmitCurrency($order->getStore()->getCode()) === $this->_moduleConfig::CURRENCY_CUSTOMER;

        /** @var Basket $basket */
        $basket = $this->basketFactory->create();
        if ($transmitInCustomerCurrency) {
            $basket->setTotalValueGross($order->getGrandTotal());
            $basket->setCurrencyCode($order->getOrderCurrencyCode());
        } else {
            $basket->setTotalValueGross($order->getBaseGrandTotal());
            $basket->setCurrencyCode($order->getBaseCurrencyCode());
        }
        $basket->setOrderId($order->getIncrementId());

        if ($order->getShippingAmount() > 0) {
            /** @var BasketItem $basketItem */
            $basketItem = $this->basketItemFactory->create();
            if ($transmitInCustomerCurrency) {
                $basketItem->setAmountDiscountPerUnitGross(abs($order->getShippingDiscountAmount()));
                $basketItem->setAmountPerUnitGross($order->getShippingInclTax());
            } else {
                $basketItem->setAmountDiscountPerUnitGross(abs($order->getBaseShippingDiscountAmount()));
                $basketItem->setAmountPerUnitGross($order->getBaseShippingInclTax());
            }
            $basketItem->setTitle('Shipment');
            $basketItem->setType(BasketItemTypes::SHIPMENT);

            $basket->addBasketItem($basketItem);
        }

        foreach ($order->getAllVisibleItems() as $orderItem) {
            /** @var OrderModel\Item $orderItem */

            // getAllVisibleItems() only checks getParentItemId() but it's possible that there is a parent item set
            // without a parent item id.
            if ($orderItem->getParentItem() !== null) {
                continue;
            }

            /** @var BasketItem $basketItem */
            $basketItem = $this->basketItemFactory->create();

            if ($transmitInCustomerCurrency) {
                $amountDiscountPerUnitGross = abs($orderItem->getDiscountAmount());
                $amountPerUnitGross = $orderItem->getPriceInclTax();
            }
            else{
                $amountDiscountPerUnitGross = abs($orderItem->getBaseDiscountAmount());
                $amountPerUnitGross = $orderItem->getBasePriceInclTax();
            }
            //add Discount as Voucher for Items qty gt 1 to prevent of 10 / 3 rounding error
            if($amountDiscountPerUnitGross > 0 && $orderItem->getQtyOrdered() > 1){
                /** @var BasketVoucherItem $basketItem */
                $basketVoucherItem = $this->basketItemFactory->create();
                $basketVoucherItem->setAmountDiscountPerUnitGross($amountDiscountPerUnitGross);
                $basketVoucherItem->setAmountPerUnitGross(0);
                $basketVoucherItem->setQuantity(1);
                $basketVoucherItem->setTitle($orderItem->getName());
                $basketVoucherItem->setType(BasketItemTypes::VOUCHER);

                $basket->addBasketItem($basketVoucherItem);

                //remove amountDiscountPerUnitGross
                $amountDiscountPerUnitGross = 0;
            }

            $basketItem->setAmountDiscountPerUnitGross($amountDiscountPerUnitGross);
            $basketItem->setAmountPerUnitGross($amountPerUnitGross);

            $basketItem->setQuantity($orderItem->getQtyOrdered());
            $basketItem->setTitle($orderItem->getName());
            $basketItem->setType($orderItem->getIsVirtual() ? BasketItemTypes::DIGITAL : BasketItemTypes::GOODS);

            $basket->addBasketItem($basketItem);
        }

        return $basket;
    }

    /**
     * Returns metadata for the given order.
     *
     * @param OrderModel $order
     * @return Metadata
     */
    public function createMetadataForOrder(OrderModel $order): Metadata
    {
        $metaData = new Metadata();

        $metaData->setShopType('Magento 2')
            ->setShopVersion($this->_productMetadata->getVersion())
            ->addMetadata('customerId', $order->getCustomerId())
            ->addMetadata('customerGroupId', $order->getCustomerGroupId())
            ->addMetadata('pluginType', 'unzerdev/magento2')
            ->addMetadata('pluginVersion', $this->_moduleList->getOne('Unzer_PAPI')['setup_version'])
            ->addMetadata('storeId', $order->getStoreId());

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
     */
    public function createCustomerFromQuote(Quote $quote, string $email, bool $createResource = false): ?Customer
    {
        // A virtual quote does not have any customer data other than E-Mail so we can't create a customer object.
        if ($quote->isVirtual()) {
            return null;
        }

        $billingAddress = $quote->getBillingAddress();

        $customer = CustomerFactory::createCustomer(
            $billingAddress->getFirstname(),
            $billingAddress->getLastname()
        );

        $customer->setSalutation($this->getSalutationFromQuote($quote));
        $customer->setEmail($email);
        $customer->setPhone($billingAddress->getTelephone());

        $company = $billingAddress->getCompany();
        if (!empty($company)) {
            $customer->setCompany($company);
        }

        $this->updateGatewayAddressFromMagento($customer->getBillingAddress(), $billingAddress);
        $this->updateGatewayAddressFromMagento($customer->getShippingAddress(), $quote->getShippingAddress());

        $client = $this->_moduleConfig->getUnzerClient();

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
     * @throws UnzerApiException
     */
    public function createCustomerFromOrder(OrderModel $order, string $email, bool $createResource = false): ?Customer
    {
        $client = $this->_moduleConfig->getUnzerClient();

        $billingAddress = $order->getBillingAddress();
        $customer = new Customer();

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
            $this->updateGatewayAddressFromMagento($customer->getShippingAddress(), $shippingAddress);
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
            ->getUnzerClient()
            ->createPaymentType(
                $method->createPaymentType()
            );
    }

    /**
     * Updates an Unzer address from an address in Magento.
     *
     * @param EmbeddedResources\Address $gatewayAddress
     * @param Quote\Address|OrderAddressInterface $magentoAddress
     */
    private function updateGatewayAddressFromMagento(
        EmbeddedResources\Address $gatewayAddress,
        $magentoAddress
    ): void {
        $street = $this->convertStreetLinesToString($magentoAddress->getStreet());

        $gatewayAddress->setName($magentoAddress->getName());
        $gatewayAddress->setCity($magentoAddress->getCity());
        $gatewayAddress->setCountry($magentoAddress->getCountryId());
        $gatewayAddress->setStreet($street);
        $gatewayAddress->setZip($magentoAddress->getPostcode());
    }

    /**
     * @param array $streetLines
     * @return string
     */
    private function convertStreetLinesToString(array $streetLines): string
    {
        $streetLines = array_map('trim', $streetLines);
        $streetLines = array_unique($streetLines);
        return implode(' ', $streetLines);
    }

    /**
     * @param OrderModel $order
     * @param Customer $gatewayCustomer
     * @throws UnzerApiException
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

        $magentoShippingAddress = $order->getShippingAddress();
        if (null !== $magentoShippingAddress) {
            $this->updateGatewayAddressFromMagento(
                $gatewayCustomer->getShippingAddress(),
                $magentoShippingAddress
            );
        }

        $client = $this->_moduleConfig->getUnzerClient();
        $client->updateCustomer($gatewayCustomer);
    }

    /**
     * Validates that the given Order and Customer have matching data.
     *
     * @param OrderModel $order
     * @param Customer $gatewayCustomer
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
     * @param EmbeddedResources\Address $gatewayAddress
     * @param OrderAddressInterface $magentoAddress
     * @return bool
     */
    private function validateGatewayAddressAgainstOrderAddress(
        EmbeddedResources\Address $gatewayAddress,
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
     * @return string
     */
    protected function getSalutationFromQuote(Quote $quote): string
    {
        return $this->getSalutationFromGender($quote->getCustomer()->getGender());
    }

    /**
     * @param float | int $gender
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

    protected function getSalutationFromPayment(InfoInterface $payment): ?string
    {
        return $payment->getAdditionalInformation('salutation');
    }

    protected function getBirthdateFromPayment(InfoInterface $payment): ?string
    {
        $birthDate = $this->birthDateFactory->create();
        $birthDate->setDate($payment->getAdditionalInformation('birthDate'));

        $date = $birthDate->getDate();
        if(is_null($date)) {
            return null;
        }
        return $date->format('Y-m-d');
    }
}
