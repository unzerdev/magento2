<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Unzer\PAPI\Model\Config;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * Adminhtml Webhook Configuration Buttons Block
 *
 * @link  https://docs.unzer.com/
 */
class GooglePayChannelId extends Field
{
    /**
     * @var Config
     */
    private Config $configHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configHelper = $configHelper;
    }

    /**
     * @inheritDoc
     *
     * @throws UnzerApiException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        if ($element->getValue() === '' || $element->getValue() === null) {
            $element->setValue($this->fetchChannelId());
        }

        return $element->getElementHtml();
    }

    /**
     * Fetch Channel ID
     *
     * @return string
     * @throws UnzerApiException
     */
    private function fetchChannelId(): string
    {
        $keyPair = $this->configHelper->getUnzerClient()->getResourceService()->fetchKeypair(true);

        foreach ($keyPair->getPaymentTypes() as $paymentType) {
            if (!property_exists($paymentType, 'type')) {
                continue;
            }
            if ($paymentType->type === 'googlepay') {
                if (!property_exists($paymentType, 'supports')) {
                    return '';
                }
                if (!is_array($paymentType->supports) || !array_key_exists(0, $paymentType->supports)) {
                    return '';
                }
                if (!property_exists($paymentType->supports[0], 'channel')) {
                    return '';
                }

                return $paymentType->supports[0]->channel;
            }
        }

        return '';
    }
}
