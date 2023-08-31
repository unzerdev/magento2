<?php
declare(strict_types=1);

namespace Unzer\PAPI\Controller\Applepay;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\AuthorizationFactory;

/**
 * Apple Pay Authorize Controller
 *
 * Copyright (C) 2021 - today Unzer GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.unzer.com/
 */
class Authorize implements CsrfAwareActionInterface
{
    public function execute()
    {
        header("Content-type: application/json; charset=utf-8");
        $jsonData = json_decode(file_get_contents('php://input'), true);

        $paymentTypeId = $jsonData['typeId'];
        $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/unzer_applepay/domain_name');
        try {
            $unzer = $this->_moduleConfig->getUnzerClient();

            $quote = $this->_checkoutSession->getQuote();

            $transaction = $unzer->authorize(number_format((float)$quote->getBaseGrandTotal(), 2),
                $quote->getBaseCurrencyCode(), $paymentTypeId, $returnController);

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
}
