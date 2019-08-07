<?php

namespace Heidelpay\Gateway2\Controller\Webhooks;

use Exception;
use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event\Manager;
use stdClass;

class Process extends Action
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
    public function execute()
    {
        /** @var HttpRequest $request */
        $request = $this->getRequest();

        /** @var HttpResponse $response */
        $response = $this->getResponse();

        if (!$this->_isRequestFromValidIp($request)) {
            $response->setStatusCode(401);
            $response->setBody('Unauthorized');
            return $response;
        }

        /** @var string $requestBody */
        $requestBody = $request->getContent();

        /** @var stdClass $event */
        $event = json_decode($requestBody);

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

        $this->_eventManager->dispatch("hpg2_{$eventKey}", [
            'resource' => $resource,
            'resourceUrl' => $event->retrieveUrl,
        ]);

        return $response;
    }

    /**
     * Returns whether the given request comes from one of the IP addresses used by the Heidelpay Gateway.
     *
     * See https://docs.heidelpay.com/docs/webhook-overview#section-what-are-webhooks
     *
     * @param HttpRequest $request
     *
     * @return bool
     */
    protected function _isRequestFromValidIp(HttpRequest $request)
    {
        /** @var string[] $clientIps */
        $clientIps = preg_split('/\s*,\s*/', $request->getClientIp(true));

        return count(array_intersect($clientIps, $this->_moduleConfig->getWebhooksSourceIps())) > 0;
    }

    /**
     * Returns whether the given webhook event is valid.
     *
     * @param stdClass $event
     *
     * @return bool
     */
    protected function _isValidEvent(stdClass $event)
    {
        return isset($event->event)
            && isset($event->publicKey)
            && isset($event->retrieveUrl)
            && $event->publicKey === $this->_moduleConfig->getPublicKey();
    }
}
