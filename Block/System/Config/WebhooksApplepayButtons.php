<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unzer\PAPI\Controller\Adminhtml\Webhooks\AbstractAction;
use UnzerSDK\Unzer;
use Magento\Backend\Block\Widget\Button;

/**
 * Adminhtml Webhook Configuration Buttons Block
 *
 * @link  https://docs.unzer.com/
 */
class WebhooksApplepayButtons extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Unzer_PAPI::system/config/webhooksapplepay.phtml';

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var bool
     */
    protected bool $_certificateIsActive = false;

    /**
     * @var Curl
     */
    protected Curl $curl;

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * WebhooksButtons constructor.
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        Curl $curl,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->curl = $curl;
        $this->serializer = $serializer;
        $this->_certificateIsActive = $this->isCertificateActive();
    }

    /**
     * Is Certificate Active
     *
     * @return bool
     */
    public function isCertificateActive(): bool
    {
        $unzerPrivateKey = base64_encode($this->_scopeConfig->getValue('payment/unzer/private_key') . ":");
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("Authorization", "Basic " . $unzerPrivateKey);

        $certificateId = $this->_scopeConfig->getValue("payment/unzer_applepay/csr_certificate_response");
        $url = $this->getApiUrl(
            $this->_scopeConfig->isSetFlag('payment/unzer/logging'),
            'v1/keypair/applepay/certificates/' . $certificateId
        );

        // get method
        $this->curl->get($url);

        // output of curl request
        $result = $this->curl->getBody();

        $result = (array)$this->serializer->unserialize($result);

        if (array_key_exists('active', $result) && $result['active'] === 1) {
            $this->_certificateIsActive = true;
        }
        return $this->_certificateIsActive;
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Get Register Private Key Action
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRegisterPrivatekeyAction(): string
    {
        return $this->getUrl('unzer/webhooks/registerapplepay', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
            'switch' => 'registerPrivateKey'
        ]);
    }

    /**
     * Get Register Private Key Button HTML
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRegisterPrivatekeyButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(Button::class);
        $button->setData([
            'id' => 'unzer_webhooks_applepay_privatekey',
            'label' => __('Register privatekey'),
            'onclick' => 'location.href = ' . $this->serializer->serialize($this->getRegisterPrivatekeyAction())
        ]);

        return $button->toHtml();
    }

    /**
     * Get Register Certificates Action
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRegisterCertificatesAction(): string
    {
        return $this->getUrl('unzer/webhooks/registerapplepay', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
            'switch' => 'registerCertificate'
        ]);
    }

    /**
     * Get Register Certificates Button HTML
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRegisterCertificatesButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(Button::class);
        $button->setData([
            'id' => 'unzer_webhooks_applepay_certificates',
            'label' => __('Register Certificate'),
            'onclick' => 'location.href = ' . $this->serializer->serialize($this->getRegisterCertificatesAction()),
        ]);

        return $button->toHtml();
    }

    /**
     * Get Activate Apple Pay Action
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getActivateApplepayAction(): string
    {
        return $this->getUrl('unzer/webhooks/registerapplepay', [
            AbstractAction::URL_PARAM_STORE => $this->getStoreIdentifier(),
            'switch' => 'activate'
        ]);
    }

    /**
     * Get Activate Apple Pay Button HTML
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getActivateApplepayButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(Button::class);

        if ($this->_certificateIsActive) {
            $button->setData([
                'id' => 'unzer_webhooks_applepay_activate',
                'label' => __('Active'),
                'onclick' => 'location.href = ' . $this->serializer->serialize($this->getActivateApplepayAction()),
                'style' => 'background-color: #ebf5d6;'
            ]);
        } else {
            $button->setData([
                'id' => 'unzer_webhooks_applepay_activate',
                'label' => __('Activate'),
                'onclick' => 'location.href = ' . $this->serializer->serialize($this->getActivateApplepayAction()),
            ]);
        }
        return $button->toHtml();
    }

    /**
     * Get Api Url
     *
     * @param bool $logging
     * @param string $url
     * @return string
     */
    public function getApiUrl(bool $logging, string $url): string
    {
        $envPrefix = 'sbx-';
        if (!$logging) {
            $envPrefix = '';
        }
        return "https://" . $envPrefix . Unzer::BASE_URL . "/" . $url;
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
