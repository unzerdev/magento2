<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Customer;

use Unzer\PAPI\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractTokenRenderer;

/**
 * PayPal Renderer
 *
 * @link  https://docs.unzer.com/
 */
class PaypalRenderer extends AbstractTokenRenderer
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * Initialize dependencies.
     *
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getIconUrl()
    {
        return ''; //$this->config->getPayPalIcon()['url'];
    }

    /**
     * @inheritdoc
     */
    public function getIconHeight()
    {
        return ''; //$this->config->getPayPalIcon()['height'];
    }

    /**
     * @inheritdoc
     */
    public function getIconWidth()
    {
        return ''; //$this->config->getPayPalIcon()['width'];
    }

    /**
     * Can render specified token
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token): bool
    {
        return $token->getPaymentMethodCode() === Config::METHOD_PAYPAL;
    }

    /**
     * Get email of PayPal payer
     *
     * @return string
     */
    public function getPayerEmail(): string
    {
        return $this->getTokenDetails()['payerEmail'] ?? (string)__('Unknown Email Address');
    }
}
