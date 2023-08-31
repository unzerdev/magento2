<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Helper\Order as OrderHelper;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\CancellationFactory;

/**
 * Cancel Command for payments
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
class CancelAuthorization extends AbstractCommand
{
    public const REASON = CancelReasonCodes::REASON_CODE_CANCEL;

    /**
     * @var CancellationFactory
     */
    private CancellationFactory $cancellationFactory;

    /**
     * Constructor
     *
     * @param Config $config
     * @param LoggerInterface $logger
     * @param OrderHelper $orderHelper
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param CancellationFactory $cancellationFactory
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        OrderHelper $orderHelper,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        CancellationFactory $cancellationFactory
    ) {
        parent::__construct(
            $config,
            $logger,
            $orderHelper,
            $urlBuilder,
            $storeManager
        );
        $this->cancellationFactory = $cancellationFactory;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function execute(array $commandSubject): void
    {
        /** @var Order\Payment $payment */
        $payment = $commandSubject['payment']->getPayment();

        $order = $payment->getOrder();

        $storeCode = $order->getStore()->getCode();

        $client = $this->_getClient($storeCode, $payment->getMethodInstance());

        $hpPayment = $client->fetchPaymentByOrderId($order->getIncrementId());

        if ($hpPayment->isCanceled()) {
            return;
        }

        $amount = null;
        if (array_key_exists('amount', $commandSubject) && $commandSubject['amount'] !== null) {
            $amount = (float)$commandSubject['amount'];
        }

        $cancellation = $this->cancellationFactory->create(['amount' => $amount]);
        $cancellation->setReasonCode(self::REASON);

        $cancellation = $client->cancelAuthorizedPayment($hpPayment, $cancellation);

        $payment->setLastTransId($cancellation->getId());
    }
}
