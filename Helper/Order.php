<?php

namespace Heidelpay\Gateway2\Helper;

use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Resources\Basket;
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
     * Returns metadata for the given order.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function createMetadata(\Magento\Sales\Model\Order $order)
    {
        return [
            'customer_id' => $order->getCustomerId(),
            'customer_group_id' => $order->getCustomerGroupId(),
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
