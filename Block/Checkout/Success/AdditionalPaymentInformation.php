<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Checkout\Success;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Unzer\PAPI\Model\Command\AbstractCommand;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;

/**
 * Onepage Checkout Success Payment Information Block
 *
 * @link  https://docs.unzer.com/
 */
class AdditionalPaymentInformation extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::success/additional_payment_information.phtml';

    /**
     * @var Session|null
     */
    protected ?Session $_checkoutSession = null;

    /**
     * @var Config
     */
    protected Config $_config;

    /**
     * AdditionalPaymentInformation constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param Config $config
     * @param array $data
     */
    public function __construct(Context $context, Session $checkoutSession, Config $config, array $data = [])
    {
        parent::__construct($context, $data);

        $this->_checkoutSession = $checkoutSession;
        $this->_config = $config;
    }

    /**
     * Returns additional payment information for the customer.
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getAdditionalPaymentInformation(): ?string
    {
        $order = $this->_checkoutSession->getLastRealOrder();

        $methodInstance = $order
            ->getPayment()
            ->getMethodInstance();

        if (!$methodInstance instanceof Base) {
            return null;
        }

        return $methodInstance->getAdditionalPaymentInformation($order);
    }

    /**
     * Returns the Unzer Payment ID of the placed order, only in sandbox (test) mode.
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getUnzerPaymentId(): ?string
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();

        if ($payment === null) {
            return null;
        }

        $methodInstance = $payment->getMethodInstance();

        if (!$methodInstance instanceof Base) {
            return null;
        }

        $storeId = $order->getStoreId() !== null ? (string)$order->getStoreId() : null;

        if (!$this->_config->isSandboxMode($storeId, $methodInstance)) {
            return null;
        }

        $paymentId = $payment->getAdditionalInformation(AbstractCommand::KEY_PAYMENT_ID);

        if (!is_string($paymentId) || $paymentId === '') {
            return null;
        }

        return $paymentId;
    }
}
