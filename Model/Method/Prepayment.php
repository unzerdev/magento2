<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * Prepayment payment method
 *
 * @link  https://docs.unzer.com/
 */
class Prepayment extends Base
{
    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * Constructor
     *
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $moduleConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param CommandPoolInterface|null $commandPool
     * @param ValidatorPoolInterface|null $validatorPool
     * @param CommandManagerInterface|null $commandExecutor
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ManagerInterface          $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory  $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        ScopeConfigInterface      $scopeConfig,
        Config                    $moduleConfig,
        PriceCurrencyInterface    $priceCurrency,
        ?CommandPoolInterface     $commandPool = null,
        ?ValidatorPoolInterface   $validatorPool = null,
        ?CommandManagerInterface  $commandExecutor = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $scopeConfig,
            $moduleConfig,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @inheritDoc
     *
     * @throws UnzerApiException|LocalizedException
     */
    public function getAdditionalPaymentInformation(Order $order): string
    {
        $payment = $this->_moduleConfig
            ->getUnzerClient($order->getStore()->getCode(), $order->getPayment()->getMethodInstance())
            ->fetchPaymentByOrderId($order->getIncrementId());

        /** @var Authorization|Charge|null $initialTransaction */
        $initialTransaction = $payment->getInitialTransaction();

        if ($initialTransaction === null) {
            return '';
        }

        $formattedAmount = $this->priceCurrency->format(
            $order->getTotalDue(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $order->getStoreId(),
            $order->getOrderCurrency()
        );

        return (string)__(
            'Please transfer the amount of %1 to the following account:<br /><br />'
            . 'Holder: %2<br/>'
            . 'IBAN: %3<br/>'
            . 'BIC: %4<br/><br/>'
            . '<i>Please use only this identification number as the descriptor: </i><br/>'
            . '%5',
            $formattedAmount,
            $initialTransaction->getHolder(),
            $initialTransaction->getIban(),
            $initialTransaction->getBic(),
            $initialTransaction->getDescriptor()
        );
    }
}
