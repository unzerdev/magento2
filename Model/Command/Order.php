<?php

namespace Heidelpay\Gateway2\Model\Command;

use Heidelpay\Gateway2\Helper\Order as OrderHelper;
use Heidelpay\Gateway2\Model\Config;
use Heidelpay\Gateway2\Model\Method\Base;
use Heidelpay\Gateway2\Model\System\Config\Source\PaymentAction;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order as OrderModel;

class Order extends AbstractCommand
{
    /**
     * @var Authorize
     */
    protected $_authorizeCommand;

    /**
     * @var Capture
     */
    protected $_captureCommand;

    /**
     * Order constructor.
     * @param Config $config
     * @param OrderHelper $orderHelper
     * @param UrlInterface $urlBuilder
     * @param Authorize $authorizeCommand
     * @param Capture $captureCommand
     */
    public function __construct(
        Config $config,
        OrderHelper $orderHelper,
        UrlInterface $urlBuilder,
        Authorize $authorizeCommand,
        Capture $captureCommand
    ) {
        parent::__construct($config, $orderHelper, $urlBuilder);

        $this->_authorizeCommand = $authorizeCommand;
        $this->_captureCommand = $captureCommand;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws HeidelpayApiException
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var OrderModel $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        /** @var Base $method */
        $method = $payment->getMethodInstance();

        /** @var string|null $action */
        $action = $method->getConfigData('order_payment_action');

        switch ($action) {
            case PaymentAction::ACTION_AUTHORIZE:
                $this->_authorizeCommand->execute($commandSubject);
                break;
            case PaymentAction::ACTION_AUTHORIZE_CAPTURE:
                $this->_captureCommand->execute($commandSubject);
                break;
            default:
                throw new \Exception('Invalid payment action');
        }

        return null;
    }
}
