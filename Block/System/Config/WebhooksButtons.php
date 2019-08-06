<?php

namespace Heidelpay\Gateway2\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Zend_Json;

class WebhooksButtons extends Field
{
    protected $_template = 'Heidelpay_Gateway2::system/config/webhooks.phtml';

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @inheritDoc
     */
    public function getRegisterAction()
    {
        return $this->getUrl('hpg2/webhooks/register');
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getRegisterButtonHtml()
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');
        $button->setData([
            'id' => 'hpg2_webhooks_register',
            'label' => __('Register webhooks'),
            'onclick' => 'location.href = '. Zend_Json::encode($this->getRegisterAction()),
        ]);

        return $button->toHtml();
    }

    /**
     * @inheritDoc
     */
    public function getUnregisterAction()
    {
        return $this->getUrl('hpg2/webhooks/unregister');
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getUnregisterButtonHtml()
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');
        $button->setData([
            'id' => 'hpg2_webhooks_unregister',
            'label' => __('Unregister webhooks'),
            'onclick' => 'location.href = '. Zend_Json::encode($this->getUnregisterAction()),
        ]);

        return $button->toHtml();
    }
}
