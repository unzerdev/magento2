<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Checkout\Data;

use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Unzer\PAPI\Api\Data\CreateRiskDataInterface;
use Unzer\PAPI\Model\Method\Observer\BaseDataAssignObserver;
use UnzerSDK\Resources\EmbeddedResources\RiskData as UnzerRiskData;
use UnzerSDK\Resources\EmbeddedResources\RiskDataFactory;

/**
 *
 * @link  https://docs.unzer.com/
 */
class CreateRiskData implements CreateRiskDataInterface
{

    /**
     * @var Payment
     */
    private Payment $payment;

    /**
     * @var Order
     */
    private Order $order;

    /**
     * @var RiskDataFactory
     */
    private RiskDataFactory $riskDataFactory;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * Constructor
     *
     * @param Payment $payment
     * @param RiskDataFactory $riskDataFactory
     * @param Session $customerSession
     */
    public function __construct(
        Payment $payment,
        RiskDataFactory $riskDataFactory,
        Session $customerSession
    ) {
        $this->riskDataFactory = $riskDataFactory;
        $this->payment = $payment;
        $this->order = $payment->getOrder();
        $this->customerSession = $customerSession;
    }

    /**
     * Execute
     *
     * @return \UnzerSDK\Resources\EmbeddedResources\RiskData|null
     */
    public function execute(): ?\UnzerSDK\Resources\EmbeddedResources\RiskData
    {
        $threatMetrixId = $this->getThreatMetrixId();

        if (!$threatMetrixId) {
            return null;
        }

        /** @var UnzerRiskData $riskData */
        $riskData = $this->riskDataFactory->create();
        $riskData->setThreatMetrixId($threatMetrixId);
        $riskData->setCustomerGroup($this->determineCustomerGroup());
        $riskData->setConfirmedOrders($this->determineConfirmedOrders());
        $riskData->setConfirmedAmount($this->determineConfirmedAmount());
        $riskData->setRegistrationLevel($this->determineRegistrationLevel());
        $riskData->setRegistrationDate($this->determineRegistrationDate());

        return $riskData;
    }

    /**
     * Get Threat Metrix ID
     *
     * @return string|null
     */
    private function getThreatMetrixId(): ?string
    {
        return $this->payment->getAdditionalInformation(BaseDataAssignObserver::KEY_THREAT_METRIX_ID);
    }

    /**
     * Determine Customer Group
     *
     * @return string
     */
    private function determineCustomerGroup(): string
    {
        return self::CUSTOMER_GROUP_NEUTRAL;
    }

    /**
     * Determine Confirmed Orders
     *
     * @return int|null
     */
    private function determineConfirmedOrders(): ?int
    {
        return null;
    }

    /**
     * Determine Confirmed Amount
     *
     * @return float|null
     */
    private function determineConfirmedAmount(): ?float
    {
        return null;
    }

    /**
     * Determine Registration Level
     *
     * @return string
     */
    private function determineRegistrationLevel(): string
    {
        return $this->order->getCustomerIsGuest() ? self::REGISTRATION_LEVEL_GUEST : self::REGISTRATION_LEVEL_CUSTOMER;
    }

    /**
     * Determine Registration Date
     *
     * @return string|null
     */
    private function determineRegistrationDate(): ?string
    {
        if ($this->order->getCustomerIsGuest()) {
            return null;
        }

        return date('Ymd', $this->customerSession->getCustomer()->getCreatedAtTimestamp());
    }
}
