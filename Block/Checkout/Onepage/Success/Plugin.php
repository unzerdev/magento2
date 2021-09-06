<?php

namespace Unzer\PAPI\Block\Checkout\Onepage\Success;

use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;

/**
 * Onepage Checkout Onepage Success Plugin
 *
 * Copyright (C) 2021 Unzer GmbH
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
class Plugin
{
    const BLOCK_NAME = 'checkout.success';

    const PENDING_TEMPLATE = 'Unzer_PAPI::success/pending.phtml';

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * Plugin constructor.
     * @param Session $checkoutSession
     */
    public function __construct(Session $checkoutSession, Config $moduleConfig)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleConfig = $moduleConfig;
    }

    /**
     * @param Template $subject
     */
    public function beforeToHtml(Template $subject): void
    {
        // There may me multiple instances of the block in the layout (e.g. checkout.success.print.button) so we
        // must check for the correct one otherwise we may get duplicate output.
        if ($subject->getNameInLayout() !== static::BLOCK_NAME) {
            return;
        }

        /** @var Order $order */
        $order = $this->_checkoutSession->getLastRealOrder();

        /** @var MethodInterface $methodInstance */
        $methodInstance = $order
            ->getPayment()
            ->getMethodInstance();

        if ($methodInstance instanceof Base && $methodInstance->hasRedirect()) {
            /** @var Payment $payment */
            try {
                $payment = $this->_moduleConfig
                    ->getHeidelpayClient()
                    ->fetchPaymentByOrderId($order->getIncrementId());

                if (($payment->getAuthorization() && $payment->getAuthorization()->isPending()) ||
                    ($payment->getChargeByIndex(0) && $payment->getChargeByIndex(0)->isPending())) {
                    $subject->setTemplate(self::PENDING_TEMPLATE);
                }
            } catch (NoSuchEntityException $e) {
            } catch (UnzerApiException $e) {
            }
        }
    }
}
