<?php

namespace Heidelpay\Gateway2\Model\Command;

use Heidelpay\Gateway2\Helper\Order;
use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Heidelpay;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\CommandInterface;

abstract class AbstractCommand implements CommandInterface
{
    /**
     * @var Heidelpay
     */
    protected $_client;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var Order
     */
    protected $_orderHelper;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * AbstractCommand constructor.
     * @param Config $config
     * @param Order $orderHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Config $config,
        Order $orderHelper,
        UrlInterface $urlBuilder
    ) {
        $this->_config = $config;
        $this->_orderHelper = $orderHelper;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Returns the URL to which customers are redirected after payment.
     *
     * @return string
     */
    protected function _getCallbackUrl(): string
    {
        return $this->_urlBuilder->getUrl('hpg2/payment/callback');
    }

    /**
     * @return Heidelpay
     * @throws NoSuchEntityException
     */
    protected function _getClient(): Heidelpay
    {
        if ($this->_client === null) {
            $this->_client = $this->_config->getHeidelpayClient();
        }

        return $this->_client;
    }
}
