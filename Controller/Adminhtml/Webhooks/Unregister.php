<?php
declare(strict_types=1);

namespace Unzer\PAPI\Controller\Adminhtml\Webhooks;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Webhook;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Controller for deregistering webhooks via the backend
 *
 * @link  https://docs.unzer.com/
 */
class Unregister extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function execute(): Redirect
    {
        try {
            $webhookUrl = $this->getWebhookUrl();

            $client = $this->_moduleConfig->getUnzerClient($this->getStoreCode());

            foreach ($client->fetchAllWebhooks() as $webhook) {
                /** @var Webhook $webhook */
                if ($webhook->getUrl() === $webhookUrl) {
                    $client->deleteWebhook($webhook);
                }
            }

            $this->messageManager->addSuccessMessage(__('Successfully unregistered webhooks'));
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
