<?php
declare(strict_types=1);

namespace Unzer\PAPI\Controller\Applepay;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

use Magento\Sales\Model\Order;
use Unzer\PAPI\Model\Config;
use Unzer\PAPI\Controller\Payment\AbstractPaymentAction;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\AuthorizationFactory;
class Authorize extends AbstractPaymentAction implements CsrfAwareActionInterface
{
    public function execute()
    {
        header("Content-type: application/json; charset=utf-8");
        $jsonData = json_decode(file_get_contents('php://input'), true);
        //$transactionType = $jsonData['transaction_type'];
        $paymentTypeId = $jsonData['typeId'];
        $returnController = 'https://applepay.c-2334.maxcluster.net';
        try {
            $unzer = $this->_moduleConfig->getUnzerClient();
            //$applePay = $unzer->fetchPaymentType($paymentTypeId);

            $quote = $this->_checkoutSession->getQuote();



            //switch ($transactionType) {
            //    case 'charge':
            //        $transaction = $unzer->charge(number_format((float)$quote->getBaseGrandTotal(),2), $quote->getBaseCurrencyCode(), $paymentTypeId, $returnController);
            //        break;
            //    default:
                    $transaction = $unzer->authorize(number_format((float)$quote->getBaseGrandTotal(),2), $quote->getBaseCurrencyCode(), $paymentTypeId, $returnController);
            //        break;
            //}

            if ($transaction->isSuccess()) {
                echo json_encode(['transactionStatus' => 'success']);
                return;
            }
            if ($transaction->isPending()) {
                echo json_encode(['transactionStatus' => 'pending']);
                return;
            }

        } catch (UnzerApiException $e) {
            echo json_encode(['transactionStatus' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            echo json_encode(['transactionStatus' => $e->getMessage()]);
        }
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function executeWith(Order $order, Payment $payment)
    {
        // TODO: Implement executeWith() method.
    }
}
