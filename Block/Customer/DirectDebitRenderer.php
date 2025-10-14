<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\Customer;

use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractTokenRenderer;
use Unzer\PAPI\Model\Config;

/**
 * @link https://docs.unzer.com/
 */
class DirectDebitRenderer extends AbstractTokenRenderer
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * Constructor
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
     * Can render specified token
     */
    public function canRender(PaymentTokenInterface $token): bool
    {
        return $token->getPaymentMethodCode() === Config::METHOD_DIRECT_DEBIT;
    }

    /**
     * Masked IBAN (e.g. DE************1234)
     */
    public function getMaskedIban(): string
    {
        return $this->getTokenDetails()['maskedIban'] ?? (string)__('Unknown IBAN');
    }

    /**
     * Account holder name
     */
    public function getAccountHolder(): string
    {
        return $this->getTokenDetails()['accountHolder'] ?? (string)__('Unknown Holder');
    }

    /**
     * Icon URL for SEPA (if you expose one in config).
     */
    public function getIconUrl()
    {
        return '';
    }

    /**
     * Icon height
     */
    public function getIconHeight()
    {
        return '';
    }

    /**
     * Icon width
     */
    public function getIconWidth()
    {
        return '';
    }
}
