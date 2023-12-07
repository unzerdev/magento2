<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Vault\Handlers;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;

/**
 * Vault Details Handler Interface
 *
 * @link  https://docs.unzer.com/
 * @api
 */
interface VaultDetailsHandlerInterface
{
    /**
     * Handle tokens
     *
     * @param PaymentDataObject $payment
     * @param AbstractTransactionType $transaction
     * @return void
     * @throws UnzerApiException
     */
    public function handle(PaymentDataObject $payment, AbstractTransactionType $transaction): void;
}
