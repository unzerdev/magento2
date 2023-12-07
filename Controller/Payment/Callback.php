<?php
declare(strict_types=1);

namespace Unzer\PAPI\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Model\Order;
use Unzer\PAPI\Helper\Payment as PaymentHelper;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;

/**
 * Callback action called when customers return from a payment provider
 *
 * @link  https://docs.unzer.com/
 */
class Callback extends AbstractPaymentAction
{
    /**
     * @var CartManagementInterface
     */
    protected CartManagementInterface $_cartManagement;

    /**
     * @var PaymentHelper
     */
    protected PaymentHelper $_paymentHelper;

    /**
     * Callback constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param Session $checkoutSession
     * @param Config $moduleConfig
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        Session $checkoutSession,
        Config $moduleConfig,
        PaymentHelper $paymentHelper
    ) {
        parent::__construct($context, $checkoutSession, $moduleConfig, $paymentHelper);

        $this->_cartManagement = $cartManagement;
    }

    /**
     * Execute With
     *
     * @param Order $order
     * @param Payment $payment
     * @return ResponseInterface
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     * @throws UnzerApiException
     */
    public function executeWith(Order $order, Payment $payment): ResponseInterface
    {
        $this->_paymentHelper->processState($order, $payment);

        return $this->_redirect('checkout/onepage/success/', ['_secure' => true]);
    }
}
