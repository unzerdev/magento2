<?php
declare(strict_types=1);

namespace Unzer\PAPI\Controller\Adminhtml\Webhooks;

use Exception;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Webhook;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Controller for registering webhooks via the backend
 *
 * @link  https://docs.unzer.com/
 */
class Register extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function execute(): Redirect
    {
        try {
            $webhookUrl = $this->getWebhookUrl();

            $client = $this->_moduleConfig->getUnzerClient($this->getStoreCode());

            $isRegistered = false;

            foreach ($client->fetchAllWebhooks() as $webhook) {
                /** @var Webhook $webhook */
                if ($webhook->getUrl() === $webhookUrl) {
                    $isRegistered = true;
                    break;
                }
            }

            if (!$isRegistered) {
                $client->createWebhook($webhookUrl, 'all');
            }

            $this->messageManager->addSuccessMessage(__('Successfully registered webhooks'));
        } catch (UnzerApiException $e) {
            $this->messageManager->addErrorMessage(__($e->getMerchantMessage()));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($this->getRequest()->getHeader('Referer'));
        return $redirect;
    }
}
