<?php

namespace Heidelpay\Gateway2\Model\Observer;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Authorization;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class PaymentFailedObserver implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * ShipmentObserver constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws HeidelpayApiException
     */
    public function execute(Observer $observer)
    {
        /** @var Authorization|Charge|Payment $payment */
        $payment = $observer->getEvent()->getData('resource');

        if (!$payment instanceof Payment) {
            $payment = $payment->getPayment();
        }

        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('increment_id', $payment->getExternalId(), 'eq')
            ->create();

        /** @var OrderInterface[] $orders */
        $orders = $this->_orderRepository->getList($searchCriteria)->getItems();

        if (count($orders) > 0) {
            $order = $orders[0];
            $order->setState(Order::STATE_PAYMENT_REVIEW);

            $this->_orderRepository->save($order);
        }
    }
}
