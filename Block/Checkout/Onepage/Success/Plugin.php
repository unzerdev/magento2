<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Checkout\Onepage\Success;

use Magento\Checkout\Block\Onepage\Success;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\InfoInterface;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Onepage Checkout Onepage Success Plugin
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
class Plugin
{
    public const BLOCK_NAME = 'checkout.success';

    public const PENDING_TEMPLATE = 'Unzer_PAPI::success/pending.phtml';

    /**
     * @var Session
     */
    protected Session $_checkoutSession;

    /**
     * @var Config
     */
    protected Config $_moduleConfig;

    /**
     * Constructor
     *
     * @param Session $checkoutSession
     * @param Config $moduleConfig
     */
    public function __construct(Session $checkoutSession, Config $moduleConfig)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleConfig = $moduleConfig;
    }

    /**
     * Before To Html
     *
     * @param Success $subject
     * @return void
     * @throws LocalizedException
     */
    public function beforeToHtml(Success $subject): void
    {
        // There may be multiple instances of the block in the layout (e.g. checkout.success.print.button) so we
        // must check for the correct one otherwise we may get duplicate output.
        if ($subject->getNameInLayout() !== static::BLOCK_NAME) {
            return;
        }

        $order = $this->_checkoutSession->getLastRealOrder();

        $payment = $order->getPayment();
        if (!$payment instanceof InfoInterface) {
            return;
        }

        $methodInstance = $payment->getMethodInstance();

        if ($methodInstance instanceof Base && $methodInstance->hasRedirect()) {
            try {
                $payment = $this->_moduleConfig
                    ->getUnzerClient($order->getStore()->getCode(), $order->getPayment()->getMethodInstance())
                    ->fetchPaymentByOrderId($order->getIncrementId());

                $initialTransaction = $payment->getInitialTransaction();
                if (($initialTransaction && $initialTransaction->isPending())) {
                    $subject->setTemplate(self::PENDING_TEMPLATE);
                }
            } catch (NoSuchEntityException|UnzerApiException $e) {
            }
        }
    }
}
