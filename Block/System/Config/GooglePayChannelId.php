<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Unzer\PAPI\Controller\Adminhtml\Webhooks\AbstractAction;

/**
 * Adminhtml Webhook Configuration Buttons Block
 *
 * @link  https://docs.unzer.com/
 */
class GooglePayChannelId extends Field
{
    /** @var string */
    protected $_template = 'Unzer_PAPI::system/config/googlepaychannelid.phtml';

    /**
     * Constructor
     *
     * @param Context $context
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $button = $this->getGooglePayChannelIdButton();

        $block = $this->_layout->createBlock(self::class);
        $block->setTemplate('Unzer_PAPI::system/config/googlepaychannelid.phtml')
            ->setChild('button', $button);
        return parent::_getElementHtml($element) . $block->toHtml();
    }

    /**
     * Get Google Pay Channel ID Button
     *
     * @return Button
     * @throws LocalizedException
     */
    public function getGooglePayChannelIdButton(): Button
    {
        $button = $this->getLayout()->createBlock(Button::class);
        $button->setData([
            'id' => 'unzer_googlepay_channelid',
            'label' => __('Fetch Gateway Merchant ID'),
        ]);
        return $button;
    }

    /**
     * Get Channel ID Action
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getChannelIdAction(): string
    {
        return $this->getUrl('unzer/googlepay/channelid', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier()
        ]);
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
        $storeIdentifier = $this->getRequest()->getParam(AbstractAction::URL_PARAM_STORE, 0);
        return (int)$this->_storeManager->getStore($storeIdentifier)->getId();
    }
}
