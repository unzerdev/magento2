<?php

namespace Heidelpay\MGW\Controller\Webhooks;

use Exception;
use Heidelpay\MGW\Model\Config;
use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Manager;
use stdClass;

/**
 * Controller for processing webhook events
 *
 * Copyright (C) 2019 heidelpay GmbH
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
 * @link  https://docs.heidelpay.com/
 *
 * @author Justin Nuß
 *
 * @package  heidelpay/magento2-merchant-gateway
 */
class Process extends Action implements CsrfAwareActionInterface
{
    /**
     * @var Manager
     */
    protected $_eventManager;

    /**
     * @var Config
     */
    protected $_moduleConfig;

    /**
     * Process constructor.
     * @param Context $context
     * @param Manager $eventManager
     * @param Config $moduleConfig
     */
    public function __construct(Context $context, Manager $eventManager, Config $moduleConfig)
    {
        parent::__construct($context);

        $this->_eventManager = $eventManager;
        $this->_moduleConfig = $moduleConfig;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResponseInterface
    {
        /** @var HttpRequest $request */
        $request = $this->getRequest();

        /** @var HttpResponse $response */
        $response = $this->getResponse();

        /** @var string $requestBody */
        $requestBody = $request->getContent();

        /** @var stdClass $event */
        $event = json_decode($requestBody, false);

        if (!$event  || !$this->_isValidEvent($event)) {
            $response->setStatusCode(400);
            $response->setBody('Bad request');
            return $response;
        }

        /** @var AbstractHeidelpayResource|null $resource */
        $resource = null;

        try {
            $resource = $this->_moduleConfig
                ->getHeidelpayClient()
                ->fetchResourceFromEvent($requestBody);
        } catch (HeidelpayApiException $e) {
            if ($e->getCode() !== ApiResponseCodes::API_ERROR_PAYMENT_NOT_FOUND &&
                $e->getCode() !== ApiResponseCodes::API_ERROR_CUSTOMER_CAN_NOT_BE_FOUND &&
                $e->getCode() !== ApiResponseCodes::API_ERROR_CUSTOMER_DOES_NOT_EXIST) {
                $response->setStatusCode(500);
                $response->setBody($e->getMerchantMessage());
            }
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->setBody($e->getMessage());
            return $response;
        }

        $eventKey = str_replace('.', '_', $event->event);

        $result = new DataObject();
        $result->setData('message', 'OK');
        $result->setData('status', 200);

        $this->_eventManager->dispatch("hpmgw_{$eventKey}", [
            'eventType' => $event->event,
            'resource' => $resource,
            'resourceUrl' => $event->retrieveUrl,
            'result' => $result,
        ]);

        $response->setHttpResponseCode($result->getData('status'));
        $response->setBody($result->getData('message'));

        return $response;
    }

    /**
     * Returns whether the given webhook event is valid.
     *
     * @param stdClass $event
     *
     * @return bool
     */
    protected function _isValidEvent(stdClass $event): bool
    {
        return isset($event->event)
            && isset($event->publicKey)
            && isset($event->retrieveUrl)
            && $event->publicKey === $this->_moduleConfig->getPublicKey();
    }
}
