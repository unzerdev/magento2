<?php
declare(strict_types=1);

namespace Unzer\PAPI\Controller\Payment;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Model\Order;
use Unzer\PAPI\Helper\Payment as PaymentHelper;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * Abstract action for accessing the current order and payment
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
abstract class AbstractPaymentAction extends Action
{
    /**
     * @var Session
     */
    protected Session $_checkoutSession;

    /**
     * @var Config
     */
    protected Config $_moduleConfig;

    /**
     * @var PaymentHelper
     */
    protected PaymentHelper $_paymentHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param Config $moduleConfig
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $moduleConfig,
        PaymentHelper $paymentHelper
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleConfig = $moduleConfig;
        $this->_paymentHelper = $paymentHelper;
    }

    /**
     * Execute
     *
     * @return HttpInterface|ResponseInterface
     */
    public function execute()
    {
        $order = $this->_checkoutSession->getLastRealOrder();

        /** @var HttpInterface $response */
        $response = $this->getResponse();

        if (!$order || !$order->getId()) {
            $response->setHttpResponseCode(400);
            $response->setBody('Bad request');
            return $response;
        }

        try {
            $payment = $this->_moduleConfig
                ->getUnzerClient($order->getStore()->getCode(), $order->getPayment()->getMethodInstance())
                ->fetchPaymentByOrderId($order->getIncrementId());

            $response = $this->executeWith($order, $payment);

            if ($payment->isCanceled()) {
                /** @var Authorization|Charge $initialTransaction */
                $initialTransaction = $payment->getInitialTransaction();
                $message = $initialTransaction->getMessage()->getCustomer();

                $response = $this->abortCheckout($message);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();

            if ($e instanceof UnzerApiException) {
                $message = $e->getClientMessage();
            }

            $response = $this->abortCheckout($message);
        }

        return $response;
    }

    /**
     * Execute With
     *
     * @param Order $order
     * @param Payment $payment
     * @return ResponseInterface
     */
    abstract public function executeWith(Order $order, Payment $payment): ResponseInterface;

    /**
     * Abort Checkout
     *
     * @param string|null $message
     * @return ResponseInterface
     */
    protected function abortCheckout(?string $message = null): ResponseInterface
    {
        $this->_checkoutSession->restoreQuote();

        if (!empty($message)) {
            $this->messageManager->addErrorMessage($message);
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}
