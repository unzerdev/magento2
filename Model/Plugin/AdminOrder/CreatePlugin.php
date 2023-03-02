<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Plugin\AdminOrder;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Unzer\PAPI\Helper\Payment as PaymentHelper;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Model\Method\Base;
use UnzerSDK\Exceptions\UnzerApiException;

class CreatePlugin
{

    /**
     * @var Config
     */
    private $moduleConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    public function __construct(
        Config $moduleConfig,
        StoreManagerInterface $storeManager,
        PaymentHelper $paymentHelper
    ) {

        $this->moduleConfig = $moduleConfig;
        $this->storeManager = $storeManager;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * @throws NoSuchEntityException
     * @throws UnzerApiException
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     */
    public function afterCreateOrder(Create $create, Order $order): Order
    {
        $payment = $order->getPayment();

        $storeCode = $this->storeManager->getStore($order->getStoreId())->getCode();

        $methodInstance = $payment->getMethodInstance();

        if (!$methodInstance instanceof Base) {
            return $order;
        }

        $payment = $this->moduleConfig
            ->getUnzerClient($storeCode, $methodInstance)
            ->fetchPaymentByOrderId($order->getIncrementId());

        $this->paymentHelper->processState($order, $payment);

        return $order;
    }
}
