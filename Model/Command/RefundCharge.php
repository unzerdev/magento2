<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Unzer\PAPI\Helper\Order as OrderHelper;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\CancellationFactory;

/**
 * Refund Command for payments
 *
 * @link  https://docs.unzer.com/
 */
class RefundCharge extends AbstractCommand
{
    public const REASON = CancelReasonCodes::REASON_CODE_RETURN;

    /**
     * @var CancellationFactory
     */
    private CancellationFactory $cancellationFactory;

    /**
     * @param Config $config
     * @param LoggerInterface $logger
     * @param OrderHelper $orderHelper
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param CancellationFactory $cancellationFactory
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        OrderHelper $orderHelper,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        CancellationFactory $cancellationFactory
    ) {
        parent::__construct(
            $config,
            $logger,
            $orderHelper,
            $urlBuilder,
            $storeManager
        );
        $this->cancellationFactory = $cancellationFactory;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws UnzerApiException
     */
    public function execute(array $commandSubject): void
    {
        /** @var OrderPayment $payment */
        $payment = $commandSubject['payment']->getPayment();

        $amount = (float)$commandSubject['amount'];

        $order = $payment->getOrder();

        $storeCode = $order->getStore()->getCode();

        $client = $this->_getClient($storeCode, $payment->getMethodInstance());

        $hpPayment = $client->fetchPaymentByOrderId($order->getIncrementId());

        if ($hpPayment->isCanceled()) {
            return;
        }

        if (count($hpPayment->getCharges()) === 0) {
            return;
        }

        $cancellation = $this->cancellationFactory->create(['amount' => $amount]);
        $cancellation->setReasonCode(self::REASON);

        $cancellation = $client->cancelChargedPayment($hpPayment, $cancellation);

        $payment->setLastTransId($cancellation->getId());
    }
}
