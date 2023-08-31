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
     * @return UnzerRiskData|null
     */
    public function execute(): ?UnzerRiskData
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
