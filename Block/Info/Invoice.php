<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Block\Info;
use Magento\Sales\Model\Order;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * Customer Account Order Invoice Information Block
 *
 * @link  https://docs.unzer.com/
 */
class Invoice extends Info
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::info/invoice.phtml';

    /**
     * @var Config
     */
    protected Config $_moduleConfig;

    /**
     * @var Payment|null
     */
    protected ?Payment $_payment = null;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Config $moduleConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $moduleConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_moduleConfig = $moduleConfig;
    }

    /**
     * @inheritDoc
     */
    public function toPdf(): string
    {
        $this->setTemplate('Unzer_PAPI::info/pdf/invoice.phtml');
        return $this->toHtml();
    }

    /**
     * Get Initial Transaction
     *
     * @return Authorization|Charge|null
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    protected function getInitialTransaction()
    {
        $transaction = $this->_getPayment()->getInitialTransaction();

        if ($transaction instanceof Authorization || $transaction instanceof Charge) {
            return $transaction;
        }
        return null;
    }

    /**
     * Has Account Data
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function hasAccountData(): bool
    {
        return $this->getInitialTransaction() !== null;
    }

    /**
     * Get Payment
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    protected function _getPayment(): Payment
    {
        if ($this->_payment === null) {
            /** @var Order $order */
            $order = $this->getInfo()->getOrder();

            $storeId = $this->getStoreCode($order->getStoreId());
            $client = $this->_moduleConfig->getUnzerClient($storeId, $order->getPayment()->getMethodInstance());

            $this->_payment = $client->fetchPaymentByOrderId($order->getIncrementId());
        }

        return $this->_payment;
    }

    /**
     * Get Account Holder
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function getAccountHolder(): ?string
    {
        $initialTransaction = $this->getInitialTransaction();
        if ($initialTransaction === null) {
            return null;
        }

        return $initialTransaction->getHolder();
    }

    /**
     * Get Account Iban
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function getAccountIban(): ?string
    {
        $initialTransaction = $this->getInitialTransaction();
        if ($initialTransaction === null) {
            return null;
        }
        return $initialTransaction->getIban();
    }

    /**
     * Get Account Bic
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function getAccountBic(): ?string
    {
        $initialTransaction = $this->getInitialTransaction();
        if ($initialTransaction === null) {
            return null;
        }
        return $initialTransaction->getBic();
    }

    /**
     * Get Reference
     *
     * @throws UnzerApiException
     * @throws LocalizedException
     */
    public function getReference(): ?string
    {
        $initialTransaction = $this->getInitialTransaction();
        if ($initialTransaction === null) {
            return null;
        }
        return $initialTransaction->getDescriptor();
    }

    /**
     * Get Order
     *
     * @throws LocalizedException
     */
    public function getOrder(): Order
    {
        $order = $this->_getData('order');
        if ($order) {
            return $order;
        }
        return $this->getInfo()->getOrder();
    }

    /**
     * Get Store Code
     *
     * @param string|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode(?string $storeId = null): string
    {
        return $this->_storeManager->getStore($storeId)->getCode();
    }
}
