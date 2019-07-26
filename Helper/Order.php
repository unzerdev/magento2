<?php

namespace Heidelpay\Gateway2\Helper;

use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer as HpCustomer;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use Magento\Store\Api\Data\StoreInterface;

class Order
{
    /**
     * @var Config
     */
    private $_moduleConfig;
    /**
     * @var StoreInterface
     */
    private $_store;

    public function __construct(Config $moduleConfig, StoreInterface $store)
    {
        $this->_moduleConfig = $moduleConfig;
        $this->_store = $store;
    }


    /**
     * Returns a Basket for the given Order.
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return Basket
     */
    public function createBasketForOrder(\Magento\Sales\Model\Order $order)
    {
        $basket = new Basket();
        $basket->setAmountTotal($order->getGrandTotal());
        $basket->setAmountTotalDiscount($order->getDiscountAmount());
        $basket->setAmountTotalVat($order->getTaxAmount());
        $basket->setCurrencyCode($order->getOrderCurrencyCode());
        $basket->setOrderId($this->getExternalId($order));

        foreach ($order->getAllVisibleItems() as $orderItem) {
            $totalInclTax = $orderItem->getRowTotalInclTax();
            if ($totalInclTax === null) {
                $totalInclTax = $orderItem->getRowTotal();
            }

            $basketItem = new BasketItem();
            $basketItem->setAmountNet($orderItem->getRowTotal());
            $basketItem->setAmountDiscount($orderItem->getDiscountAmount());
            $basketItem->setAmountGross($totalInclTax);
            $basketItem->setAmountPerUnit($orderItem->getPrice());
            $basketItem->setAmountVat($orderItem->getTaxAmount());
            $basketItem->setQuantity($orderItem->getQtyOrdered());
            $basketItem->setTitle($orderItem->getName());

            $basket->addBasketItem($basketItem);
        }

        return $basket;
    }

    /**
     * Returns the Heidelpay Customer object for the given order.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return HpCustomer|null
     * @throws HeidelpayApiException
     */
    public function createOrUpdateCustomerForOrder(\Magento\Sales\Model\Order $order)
    {
        if ($order->getCustomerIsGuest()) {
            return null;
        }

        $client = $this->_moduleConfig->getHeidelpayClient($this->_store->getLocaleCode());

        try {
            return $client->fetchCustomerByExtCustomerId($order->getCustomerId());
        } catch (HeidelpayApiException $e) {
            if ($e->getCode() !== ApiResponseCodes::API_ERROR_CUSTOMER_DOES_NOT_EXIST) {
                throw $e;
            }
        }

        $hpCustomer = new HpCustomer();
        $hpCustomer->setBirthDate($order->getCustomerDob());
        $hpCustomer->setCustomerId($order->getCustomerId());
        $hpCustomer->setEmail($order->getCustomerEmail());
        $hpCustomer->setFirstname($order->getCustomerFirstname());
        $hpCustomer->setLastname($order->getCustomerLastname());

        return $client->createOrUpdateCustomer($hpCustomer);
    }

    /**
     * Returns metadata for the given order.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function createMetadata(\Magento\Sales\Model\Order $order)
    {
        return [
            'increment_id' => $order->getIncrementId(),
            'store_id' => $order->getStoreId(),
        ];
    }

    /**
     * Returns the external ID for the given order.
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getExternalId(\Magento\Sales\Model\Order $order)
    {
        return "{$order->getStoreId()}-{$order->getIncrementId()}";
    }
}