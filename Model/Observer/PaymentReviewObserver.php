<?php

namespace Heidelpay\Gateway2\Model\Observer;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class PaymentReviewObserver implements ObserverInterface
{
    /**
     * @var OrderManagementInterface
     */
    protected $_orderManagement;

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
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->_orderManagement = $orderManagement;
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
        /** @var Payment $payment */
        $payment = $observer->getEvent()->getData('resource');

        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('increment_id', $payment->getExternalId(), 'eq')
            ->create();

        /** @var OrderInterface[] $orders */
        $orders = $this->_orderRepository->getList($searchCriteria)->getItems();

        if (count($orders) > 0) {
            $this->_orderManagement->cancel($orders[0]->getId());
        }
    }
}
