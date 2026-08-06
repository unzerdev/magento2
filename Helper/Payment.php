<?php
declare(strict_types=1);

namespace Unzer\PAPI\Helper;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Unzer\PAPI\Model\Method\Base;
use Unzer\PAPI\Model\Payment\Status\OrderStateApplier;
use Unzer\PAPI\Model\Payment\Status\Processor\ProcessorPool;
use Unzer\PAPI\Model\Vault\VaultDetailsHandlerManager;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment as PaymentResource;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;

/**
 * Thin coordinator on top of the Unzer payment-status processor pool.
 *
 * Holds the cross-cutting concerns that aren't specific to a single
 * Unzer state — the per-order lock, vault-token persistence, and the
 * public {@see setOrderState} façade still consumed by
 * Controller\Payment\Redirect — then delegates the actual state-by-state
 * mapping to a {@see ProcessorPool}-resolved processor.
 *
 * @link  https://docs.unzer.com/
 */
class Payment
{
    public const STATUS_READY_TO_CAPTURE = 'unzer_ready_to_capture';

    /**
     * @var LockManagerInterface
     */
    private LockManagerInterface $_lockManager;

    /**
     * @var OrderRepository
     */
    private OrderRepository $_orderRepository;

    /**
     * @var ProcessorPool
     */
    private ProcessorPool $processorPool;

    /**
     * @var OrderStateApplier
     */
    private OrderStateApplier $orderStateApplier;

    /**
     * @var VaultDetailsHandlerManager
     */
    private VaultDetailsHandlerManager $vaultDetailsHandlerManager;

    /**
     * @var PaymentDataObjectFactoryInterface
     */
    private PaymentDataObjectFactoryInterface $paymentDataObjectFactory;

    /**
     * @param LockManagerInterface $lockManager
     * @param OrderRepository $orderRepository
     * @param ProcessorPool $processorPool
     * @param OrderStateApplier $orderStateApplier
     * @param VaultDetailsHandlerManager $vaultDetailsHandlerManager
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     */
    public function __construct(
        LockManagerInterface $lockManager,
        OrderRepository $orderRepository,
        ProcessorPool $processorPool,
        OrderStateApplier $orderStateApplier,
        VaultDetailsHandlerManager $vaultDetailsHandlerManager,
        PaymentDataObjectFactoryInterface $paymentDataObjectFactory
    ) {
        $this->_lockManager = $lockManager;
        $this->_orderRepository = $orderRepository;
        $this->processorPool = $processorPool;
        $this->orderStateApplier = $orderStateApplier;
        $this->vaultDetailsHandlerManager = $vaultDetailsHandlerManager;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * Apply the Unzer payment state to the given Magento order under a
     * per-order lock. Vault details run once up-front, then the
     * method-specific processor takes over the state-by-state mapping.
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     * @throws UnzerApiException
     */
    public function processState(OrderInterface $order, PaymentResource $payment): void
    {
        $lockName = sprintf('unzer_order_%d', $order->getId());

        $this->_lockManager->lock($lockName);

        // Reload order to get current state
        $order = $this->_orderRepository->get($order->getId());

        try {
            $this->processVaultDetails($order, $payment);

            $processor = $this->processorPool->get($order->getPayment()->getMethod());
            $processor->process($order, $payment);
        } finally {
            $this->_lockManager->unlock($lockName);
        }
    }

    /**
     * Resolve, persist, and email the order's state/status. Public for
     * Controller\Payment\Redirect, which sets the pre-redirect state
     * directly before sending the customer to the provider URL.
     *
     * @param OrderInterface $order
     * @param string|null $state
     * @param string|null $status
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function setOrderState(OrderInterface $order, ?string $state = null, ?string $status = null): void
    {
        $this->orderStateApplier->setOrderState($order, $state, $status);
    }

    /**
     * Persist a vault token for the just-completed payment when the
     * method opts into "save on success". Runs ahead of state routing
     * since the token can be reused even for non-completed states.
     *
     * @param OrderInterface $order
     * @param PaymentResource $payment
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NotFoundException
     * @throws UnzerApiException
     */
    private function processVaultDetails(OrderInterface $order, PaymentResource $payment): void
    {
        if ($payment->getState() === PaymentState::STATE_CANCELED) {
            return;
        }

        $methodInstance = $order->getPayment()->getMethodInstance();
        if (!$methodInstance instanceof Base) {
            return;
        }

        if ($methodInstance->getVaultCode() === null) {
            return;
        }

        if ($methodInstance->isCreateVaultTokenOnSuccess() !== true) {
            return;
        }

        $transactionType = $payment->getInitialTransaction();
        if (!$transactionType instanceof AbstractTransactionType) {
            return;
        }

        $paymentMethodCode = $methodInstance->getCode();
        $paymentDataObject = $this->paymentDataObjectFactory->create($order->getPayment());

        $this->vaultDetailsHandlerManager->getHandlerByCode($paymentMethodCode)
            ->handle($paymentDataObject, $transactionType);
    }
}
