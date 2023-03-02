<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Command\Authorize;

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
 *
 * @package  unzerdev/magento2
 */
class CreateRiskData implements CreateRiskDataInterface
{

    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var RiskDataFactory
     */
    private $riskDataFactory;

    /**
     * @var Session
     */
    private $customerSession;

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

    private function getThreatMetrixId(): ?string
    {
        return $this->payment->getAdditionalInformation(BaseDataAssignObserver::KEY_THREAT_METRIX_ID);
    }

    private function determineCustomerGroup(): string
    {
        return self::CUSTOMER_GROUP_NEUTRAL;
    }

    private function determineConfirmedOrders(): ?int
    {
        return null;
    }

    private function determineConfirmedAmount(): ?float
    {
        return null;
    }

    private function determineRegistrationLevel(): string
    {
        return $this->order->getCustomerIsGuest() ? self::REGISTRATION_LEVEL_GUEST : self::REGISTRATION_LEVEL_CUSTOMER;
    }

    private function determineRegistrationDate(): ?string
    {
        if ($this->order->getCustomerIsGuest()) {
            return null;
        }

        return date('Ymd', $this->customerSession->getCustomer()->getCreatedAtTimestamp());
    }
}
