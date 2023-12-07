<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config;

use Magento\Framework\Serialize\Serializer\Json;
use Unzer\PAPI\Controller\Adminhtml\Webhooks\AbstractAction;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Block\Widget\Button;

/**
 * Adminhtml Webhook Configuration Buttons Block
 *
 * @link  https://docs.unzer.com/
 */
class WebhooksButtons extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::system/config/webhooks.phtml';

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Json
     */
    protected Json $jsonSerializer;

    /**
     * WebhooksButtons constructor.
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Get Register Action
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRegisterAction(): string
    {
        return $this->getUrl('unzer/webhooks/register', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
        ]);
    }

    /**
     * Get Register Button Html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getRegisterButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(Button::class);
        $button->setData([
            'id' => 'unzer_webhooks_register',
            'label' => __('Register webhooks'),
            'onclick' => 'location.href = ' . $this->jsonSerializer->serialize(
                $this->getRegisterAction()
            ),
        ]);

        return $button->toHtml();
    }

    /**
     * Get Unregister Action
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getUnregisterAction(): string
    {
        return $this->getUrl('unzer/webhooks/unregister', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
        ]);
    }

    /**
     * Get Unregister Button Html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getUnregisterButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(Button::class);
        $button->setData([
            'id' => 'unzer_webhooks_unregister',
            'label' => __('Unregister webhooks'),
            'onclick' => 'location.href = ' . $this->jsonSerializer->serialize(
                $this->getUnregisterAction()
            ),
        ]);

        return $button->toHtml();
    }

    /**
     * Get Store Identifier
     *
     * @return int
     * @throws NoSuchEntityException
     */
    protected function getStoreIdentifier(): int
    {
        /** @var int|string $storeIdentifier */
        $storeIdentifier = $this->getRequest()->getParam(AbstractAction::URL_PARAM_STORE);

        return (int)$this->_storeManager->getStore($storeIdentifier)->getId();
    }
}
