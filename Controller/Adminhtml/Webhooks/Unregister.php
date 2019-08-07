<?php

namespace Heidelpay\Gateway2\Controller\Adminhtml\Webhooks;

use Heidelpay\Gateway2\Helper\Webhooks as WebhooksHelper;
use Heidelpay\Gateway2\Model\Config;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Webhook;
use Magento\Backend\App\Action;

class Unregister extends Action
{
    /**
     * @var Config Config
     */
    protected $_moduleConfig;

    /**
     * @var WebhooksHelper
     */
    protected $_webhooksHelper;

    /**
     * Unregister constructor.
     * @param Action\Context $context
     * @param Config $moduleConfig
     * @param WebhooksHelper $webhooksHelper
     */
    public function __construct(Action\Context $context, Config $moduleConfig, WebhooksHelper $webhooksHelper)
    {
        parent::__construct($context);

        $this->_moduleConfig = $moduleConfig;
        $this->_webhooksHelper = $webhooksHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var string $webhookUrl */
        $webhookUrl = $this->_webhooksHelper->getUrl();

        try {
            $client = $this->_moduleConfig->getHeidelpayClient();

            foreach ($client->fetchAllWebhooks() as $webhook) {
                /** @var Webhook $webhook */
                if ($webhook->getUrl() === $webhookUrl) {
                    $client->deleteWebhook($webhook);
                }
            }

            $this->messageManager->addSuccess(__('Successfully unregistered webhooks'));
        } catch (HeidelpayApiException $e) {
            $this->messageManager->addError(__($e->getMerchantMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($this->getRequest()->getHeader('Referer'));
        return $redirect;
    }
}
