<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Vault\Handlers;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

/**
 * Manual vault token persistence for redirect-based payment methods.
 *
 * @link  https://docs.unzer.com/
 */
class VaultTokenPersister
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private PaymentTokenManagementInterface $paymentTokenManagement;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        EncryptorInterface $encryptor
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->encryptor = $encryptor;
    }

    /**
     * @param PaymentTokenInterface $paymentToken
     * @param OrderPaymentInterface $payment
     *
     * @return void
     */
    public function save(PaymentTokenInterface $paymentToken, OrderPaymentInterface $payment): void
    {
        if (!$paymentToken->getGatewayToken()) {
            return;
        }

        $order = $payment->getOrder();
        if (!$order) {
            return;
        }

        if ($paymentToken->getEntityId()) {
            $this->paymentTokenManagement->addLinkToOrderPayment(
                $paymentToken->getEntityId(),
                $payment->getEntityId()
            );

            return;
        }

        $paymentToken->setCustomerId($order->getCustomerId());
        $paymentToken->setIsActive(true);
        $paymentToken->setPaymentMethodCode($payment->getMethod());

        $additionalInformation = $payment->getAdditionalInformation();
        $paymentToken->setIsVisible(
            (bool)(int)($additionalInformation[VaultConfigProvider::IS_ACTIVE_CODE] ?? 0)
        );

        $paymentToken->setPublicHash(
            $this->generatePublicHash($paymentToken, (int)$order->getCustomerId())
        );

        $this->paymentTokenManagement->saveTokenWithPaymentLink($paymentToken, $payment);
    }

    /**
     * @param PaymentTokenInterface $paymentToken
     * @param int|null $customerId
     *
     * @return string
     */
    private function generatePublicHash(PaymentTokenInterface $paymentToken, ?int $customerId): string
    {
        return $this->encryptor->getHash(
            ($customerId ?: '')
            . $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails()
        );
    }
}
