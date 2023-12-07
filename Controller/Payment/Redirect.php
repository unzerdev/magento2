<?php
declare(strict_types=1);

namespace Unzer\PAPI\Controller\Payment;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
     */
    public function executeWith(Order $order, Payment $payment): ResponseInterface
    {
        /** @var Authorization|Charge $transaction */
        $transaction = $payment->getInitialTransaction();

        if ($transaction->isError()) {
            return $this->abortCheckout($transaction->getMessage()->getCustomer());
        }

        $this->_paymentHelper->setOrderState($order, Order::STATE_NEW, self::STATUS_PENDING);

        /** @var string|null $redirectUrl */
        $redirectUrl = $transaction->getRedirectUrl();

        if (empty($redirectUrl)) {
            $redirectUrl = $transaction->getReturnUrl();
        } else {
            // We have to adjust the order status/state for PayPal and similar payment methods,
            // so orders don't get stuck on pending status after the customer aborts the payment authorization,
            // like closing the browser instead of cancelling on PayPal login page.
            $this->_paymentHelper->setOrderState($order, Order::STATE_PENDING_PAYMENT, self::STATUS_PENDING_PAYMENT);
        }

        return $this->_redirect($redirectUrl);
    }
}
