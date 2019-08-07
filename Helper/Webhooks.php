<?php

namespace Heidelpay\Gateway2\Helper;

use Magento\Framework\Url;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Webhooks
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Url
     */
    protected $_urlBuilder;

    /**
     * Webhooks constructor.
     * @param StoreManagerInterface $storeManager
     * @param Url $urlBuilder
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Url $urlBuilder
    ) {
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        /** @var Store $store */
        $store = $this->_storeManager->getDefaultStoreView();

        return $this->_urlBuilder
            ->setScope($store)
            ->getUrl('hpg2/webhooks/process', ['_nosid' => true]);
    }
}
