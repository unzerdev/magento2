<?php
declare(strict_types=1);

namespace Unzer\PAPI\Controller\Payment;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\Order;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * Action for redirecting customers to payment providers
 *
 * @link  https://docs.unzer.com/
 */
class Redirect extends AbstractPaymentAction
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_PENDING_PAYMENT = 'pending_payment';

    /**
     * @inheritDoc
     *
     * @throws UnzerApiException
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function executeWith(Order $order, Payment $payment): ResponseInterface
    {
        /** @var Authorization|Charge $transaction */
        $transaction = $payment->getInitialTransaction();

        if ($transaction->isError()) {
            return $this->abortCheckout($transaction->getMessage()->getCustomer());
        }

        /** @var string|null $redirectUrl */
        $redirectUrl = $transaction->getRedirectUrl();

        if (empty($redirectUrl)) {
            $authorization = $payment->getAuthorization();

            if ($authorization !== null && $authorization->isSuccess()) {
                // The authorization is already final on Unzer's side, so apply it right away under the order lock.
                // Resetting the order to pending here would overwrite a state a webhook may already have applied
                // and leave the order stuck on pending if the callback never arrives.
                $this->_paymentHelper->processState($order, $payment);
            } else {
                $this->_paymentHelper->setOrderState($order, Order::STATE_NEW, self::STATUS_PENDING);
            }

            return $this->_redirect($transaction->getReturnUrl());
        }

        $this->_paymentHelper->setOrderState($order, Order::STATE_NEW, self::STATUS_PENDING);

        // We have to adjust the order status/state for PayPal and similar payment methods,
        // so orders don't get stuck on pending status after the customer aborts the payment authorization,
        // like closing the browser instead of cancelling on PayPal login page.
        $this->_paymentHelper->setOrderState($order, Order::STATE_PENDING_PAYMENT, self::STATUS_PENDING_PAYMENT);

        return $this->_redirect($redirectUrl);
    }
}
