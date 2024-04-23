<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Command;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment\Operations\AuthorizeOperation;
use Magento\Sales\Model\Order\Payment\Operations\CaptureOperation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Helper\Order as OrderHelper;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use Unzer\PAPI\Model\System\Config\Source\PaymentAction;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 *
 * @link  https://docs.unzer.com/
 */
class OrderAuthorizeAndCapture extends AbstractCommand
{
    /**
     * @var AuthorizeOperation
     */
    protected AuthorizeOperation $_authorizeOperation;

    /**
     * @var CaptureOperation
     */
    protected CaptureOperation $_captureOperation;

    /**
     * Constructor
     *
     * @param Config $config
     * @param LoggerInterface $logger
     * @param OrderHelper $orderHelper
     * @param UrlInterface $urlBuilder
     * @param AuthorizeOperation $authorizeOperation
     * @param CaptureOperation $captureOperation
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        OrderHelper $orderHelper,
        UrlInterface $urlBuilder,
        AuthorizeOperation $authorizeOperation,
        CaptureOperation $captureOperation,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct(
            $config,
            $logger,
            $orderHelper,
            $urlBuilder,
            $storeManager
        );

        $this->_authorizeOperation = $authorizeOperation;
        $this->_captureOperation = $captureOperation;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws UnzerApiException
     * @throws Exception
     */
    public function execute(array $commandSubject): ?ResultInterface
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $commandSubject['payment']->getPayment();

        $amount = (float)$commandSubject['amount'];

        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        /** @var Base $method */
        $method = $payment->getMethodInstance();

        /** @var string|null $action */
        $action = $method->getConfigData('order_payment_action');

        switch ($action) {
            case PaymentAction::ACTION_AUTHORIZE:
                $this->_authorizeOperation->authorize($payment, true, $amount);
                break;
            case PaymentAction::ACTION_AUTHORIZE_CAPTURE:
                $payment = $this->_authorizeOperation->authorize($payment, true, $amount);
                $payment = $this->_captureOperation->capture($payment, null);
                break;
            default:
                throw new Exception('Invalid payment action');
        }

        // Don't create a transaction for the Order command itself.
        $payment->setSkipOrderProcessing(true);

        return null;
    }
}
