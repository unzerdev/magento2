<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template\Context;
use Unzer\PAPI\Model\Config;

/**
 * Authorize Command for payments
 *
 * @link  https://docs.unzer.com/
 */
class Currency implements OptionSourceInterface
{

    /**
     * @var Context
     */
    private Context $context;

    /**
     * Constructor
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Returns current base currency code
     *
     * @return string
     * @throws LocalizedException
     */
    protected function getBaseCurrencyCode(): string
    {
        $requestParams = $this->context->getRequest()->getParams();
        $storeManager = $this->context->getStoreManager();
        if (isset($requestParams['website'])) {
            return $storeManager->getWebsite($requestParams['website'])->getBaseCurrencyCode();
        }

        if (isset($requestParams['store'])) {
            return $storeManager->getStore($requestParams['store'])->getBaseCurrencyCode();
        }

        // storeId 0 = Default Config
        return $storeManager->getStore(0)->getBaseCurrencyCode();
    }

    /**
     * Return currency options
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Config::CURRENCY_BASE,
                'label' => __('Base Currency').' ('.$this->getBaseCurrencyCode().')'
            ],
            [
                'value' => Config::CURRENCY_CUSTOMER,
                'label' => __('Customer Currency')
            ]
        ];
    }
}
