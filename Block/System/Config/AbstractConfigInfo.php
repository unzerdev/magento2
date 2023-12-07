<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ResourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adminhtml Abstract Info Block
 *
 * @link  https://docs.unzer.com/
 */
class AbstractConfigInfo extends Field
{
    /**
     * @var string
     */
    protected $_template = '';

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var ResourceInterface
     */
    protected ResourceInterface $moduleResource;

    /**
     * WebhooksButtons constructor.
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param ResourceInterface $moduleResource
     * @param array $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        ResourceInterface $moduleResource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Get Info
     *
     * @return string
     */
    public function getInfo(): string
    {
        return '';
    }

    /**
     * Render Scope Label
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _renderScopeLabel(AbstractElement $element): string
    {
        return '';
    }
}
