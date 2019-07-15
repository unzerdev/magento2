<?php

namespace Heidelpay\Gateway2\Model\Method;

use Heidelpay\Gateway2\Config\Module as ModuleConfig;
use heidelpayPHP\Heidelpay;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Api\Data\StoreInterface;

class Base extends AbstractMethod
{
    protected $_code = \Heidelpay\Gateway2\Config\Method\Creditcard::METHOD_CODE;

    /**
     * @var Heidelpay
     */
    protected $_client;

    /**
     * @var ModuleConfig
     */
    protected $_moduleConfig;

    /**
     * @var StoreInterface
     */
    protected $_store;

    /**
     * Base constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ModuleConfig $moduleConfig
     * @param StoreInterface $store
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param DirectoryHelper|null $directory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ModuleConfig $moduleConfig,
        StoreInterface $store,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    )
    {
        $this->_moduleConfig = $moduleConfig;
        $this->_store = $store;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
    }

    /**
     * Returns the gateway client.
     *
     * @return Heidelpay
     */
    protected function _getClient()
    {
        if ($this->_client === null) {
            $this->_client = new Heidelpay($this->_moduleConfig->getPrivateKey(), $this->_store->getLocaleCode());
        }

        return $this->_client;
    }
}