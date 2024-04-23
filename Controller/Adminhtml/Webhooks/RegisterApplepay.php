<?php
declare(strict_types=1);

namespace Unzer\PAPI\Controller\Adminhtml\Webhooks;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as configCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use UnzerSDK\Services\EnvironmentService;
use UnzerSDK\Services\HttpService;
use UnzerSDK\Unzer;

/**
 * Controller for registering Certificates via the backend
 *
 * @link  https://docs.unzer.com/
 *
 *
 */
class RegisterApplepay extends Action
{
    private const URL_PART_STAGING_ENVIRONMENT = 'stg';
    private const URL_PART_DEVELOPMENT_ENVIRONMENT = 'dev';
    private const URL_PART_SANDBOX_ENVIRONMENT = 'sbx';
    private const PATH_UPLOAD_APPLEPAY_CSR = '../app/etc/upload/applepay_csr/';

    /** @var Curl $curl */
    private Curl $curl;

    /** @var ScopeConfigInterface $_scopeConfig */
    private ScopeConfigInterface $_scopeConfig;

    /** @var WriterInterface $_configWriter */
    private WriterInterface $_configWriter;

    /** @var configCollection $configCollection */
    private configCollection $configCollection;

    /** @var HttpService $httpService */
    private HttpService $httpService;

    /** @var DriverInterface $driver */
    private DriverInterface $driver;

    /** @var SerializerInterface $serializer */
    private SerializerInterface $serializer;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param WriterInterface $configWriter
     * @param configCollection $configCollection
     * @param HttpService $httpService
     * @param Session $session
     * @param DriverInterface $driver
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Action\Context       $context,
        ScopeConfigInterface $scopeConfig,
        Curl                 $curl,
        WriterInterface      $configWriter,
        ConfigCollection     $configCollection,
        HttpService          $httpService,
        Session              $session,
        DriverInterface      $driver,
        SerializerInterface  $serializer
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->_configWriter = $configWriter;
        $this->configCollection = $configCollection;
        $this->httpService = $httpService;
        $this->_session = $session;
        $this->driver = $driver;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     *
     * @throws FileSystemException
     */
    public function execute(): Redirect
    {
        $this->getRequest()->getParams();
        $mode = $this->getRequest()->getParam('switch');
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $publicKey = $this->_scopeConfig->getValue('payment/unzer/public_key', 'store', $storeId);
        $privateKey = $this->_scopeConfig->getValue('payment/unzer/private_key', 'store', $storeId);
        $unzerPrivateKey = base64_encode($privateKey . ":");
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("Authorization", "Basic " . $unzerPrivateKey);

        $sslCert = $this->getSslCert($storeId);
        $sslKey = $this->getSslKey($storeId);

        switch ($mode) {

            case 'registerPrivateKey':
                $this->registerPrivateKey($sslKey, $privateKey, $publicKey);
                break;

            case 'registerCertificate':
                $this->registerCertificate($sslCert, $publicKey);
                break;

            case 'activate':
                $this->activateCertificate($privateKey, $publicKey);
                break;
        }
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($this->getRequest()->getHeader('Referer') . $this->getAnchorLink());
        return $redirect;
    }

    /**
     * Handle Result
     *
     * @param array $result
     * @param string $publicKey
     * @return void
     */
    public function handleResult(array $result, string $publicKey)
    {
        if (array_key_exists('errors', $result)) {
            $errors = current($result['errors']);
            $errorMessage = $errors->code . " - " . $errors->merchantMessage;
            $this->messageManager->addErrorMessage(__($errorMessage));
        } else {
            $this->_configWriter->save(
                'payment/unzer_applepay/csr_certificate_response',
                $result['id']
            );
            $this->messageManager->addSuccessMessage(__('Successfully registered for public key: ' . $publicKey));
        }
    }
    /**
     * Get Anchor Link
     *
     * @return string
     */
    public function getAnchorLink(): string
    {
        $anchor = "#payment_us_unzer_applepay-link";
        $configState = (array) $this->_session->getUser()->getExtra();
        $configStateKeys = '';
        if (!empty($configState) && array_key_exists('configState', $configState)) {
            $configStateKeys = array_keys($configState['configState']);
        }
        $dataForAnchorGeneration = preg_grep('/.*unzer.*/', $configStateKeys);
        if (!empty($dataForAnchorGeneration)) {
            $anchor = "#" . current($dataForAnchorGeneration) . "_applepay-link";
        }
        return $anchor;
    }

    /**
     * Get Registration Response Value
     *
     * @param string $path
     * @return string
     */
    public function getRegistrationResponseValue(string $path): string
    {
        $collection = $this->configCollection->create();
        $collection->addFieldToFilter("path", ['eq' => $path]);
        if ($collection->count() > 0) {
            return (string)$collection->getFirstItem()->getData()['value'];
        } else {
            return "";
        }
    }

    /**
     * Get Api Url
     *
     * @param string $privateKey
     * @param string $url
     * @return string
     */
    public function getApiUrl(string $privateKey, string $url): string
    {
        $envPrefix = $this->getEnvironmentPrefix($privateKey);
        return "https://" . $envPrefix . Unzer::BASE_URL . "/" . $url;
    }

    /**
     * Get Environment Prefix
     *
     * @param string $privateKey
     * @return string
     */
    public function getEnvironmentPrefix(string $privateKey): string
    {
        // Production Environment uses no prefix.
        if ($this->isProductionKey($privateKey)) {
            return '';
        }

        switch ($this->httpService->getEnvironmentService()->getPapiEnvironment()) {
            case EnvironmentService::ENV_VAR_VALUE_STAGING_ENVIRONMENT:
                $envPrefix = self::URL_PART_STAGING_ENVIRONMENT;
                break;
            case EnvironmentService::ENV_VAR_VALUE_DEVELOPMENT_ENVIRONMENT:
                $envPrefix = self::URL_PART_DEVELOPMENT_ENVIRONMENT;
                break;
            default:
                $envPrefix = self::URL_PART_SANDBOX_ENVIRONMENT;
        }
        return $envPrefix . '-';
    }

    /**
     * Determine whether key is for production environment.
     *
     * @param string $privateKey
     *
     * @return bool
     */
    private function isProductionKey(string $privateKey): bool
    {
        return strpos($privateKey, 'p') === 0;
    }

    /**
     * Get Upload Path for Certificate
     *
     * @param int $storeId
     * @return string
     */
    protected function getUploadPathForCertificate(int $storeId): string
    {
        return self::PATH_UPLOAD_APPLEPAY_CSR . $this->_scopeConfig->getValue(
            'payment/unzer/applepay/csr_certificate_upload',
            'store',
            $storeId
        );
    }

    /**
     * Get Upload Path for Private Key
     *
     * @param int $storeId
     * @return string
     */
    protected function getUploadPathForPrivateKey(int $storeId): string
    {
        return self::PATH_UPLOAD_APPLEPAY_CSR . $this->_scopeConfig->getValue(
            'payment/unzer/applepay/csr_privat_key_upload',
            'store',
            $storeId
        );
    }

    /**
     * Get SSL Certificate
     *
     * @param int $storeId
     * @return string
     * @throws FileSystemException
     */
    protected function getSslCert(int $storeId): string
    {
        $sslCertPath = $this->getUploadPathForCertificate($storeId);
        $sslCert = $this->driver->fileGetContents($sslCertPath);
        $sslCert = str_replace('-----BEGIN CERTIFICATE-----', '', $sslCert);
        $sslCert = str_replace('-----END CERTIFICATE-----', '', $sslCert);
        return str_replace("\n", "", $sslCert);
    }

    /**
     * Get SSL Key
     *
     * @param int $storeId
     * @return array|string|string[]
     * @throws FileSystemException
     */
    protected function getSslKey(int $storeId): string
    {
        $sslKeyPath = $this->getUploadPathForPrivateKey($storeId);

        $sslKey = $this->driver->fileGetContents($sslKeyPath);
        $sslKey = str_replace('-----BEGIN PRIVATE KEY-----', '', $sslKey);
        $sslKey = str_replace('-----END PRIVATE KEY-----', '', $sslKey);
        return str_replace("\n", "", $sslKey);
    }

    /**
     * Register Private Key
     *
     * @param string $sslKey
     * @param string $privateKey
     * @param string $publicKey
     * @return void
     */
    protected function registerPrivateKey(string $sslKey, string $privateKey, string $publicKey): void
    {
        $url = $this->getApiUrl($privateKey, 'v1/keypair/applepay/privatekeys');

        $params = [
            'format' => 'PEM',
            'type' => 'private-key',
            'certificate' => $sslKey
        ];

        // post method
        $this->curl->post($url, $this->serializer->serialize($params));

        // output of curl request
        $result = $this->curl->getBody();

        $result = (array)$this->serializer->unserialize($result);

        if (array_key_exists('errors', $result)) {
            $errors = current($result['errors']);
            $errorMessage = $errors->code . " - " . $errors->merchantMessage;
            $this->messageManager->addErrorMessage(__($errorMessage));
        } else {
            $this->_configWriter->save(
                'payment/unzer_applepay/csr_private_key_response',
                $result['id']
            );
            $this->messageManager->addSuccessMessage(
                __('Successfully registered for public key: ' . $publicKey)
            );
        }
    }

    /**
     * Register Certificate
     *
     * @param string $sslCert
     * @param string $publicKey
     * @return void
     */
    protected function registerCertificate(string $sslCert, string $publicKey): void
    {
        $privateKey = $this->getRegistrationResponseValue("payment/unzer_applepay/csr_private_key_response");
        $url = $this->getApiUrl($privateKey, 'v1/keypair/applepay/certificates');

        $params = [
            'format' => 'PEM',
            'type' => 'certificate',
            'private-key' => $privateKey,
            'certificate' => $sslCert
        ];

        // post method
        $this->curl->post($url, $this->serializer->serialize($params));

        // output of curl request
        $result = $this->curl->getBody();

        $result = (array)$this->serializer->unserialize($result);

        $this->handleResult($result, $publicKey);
    }

    /**
     * Activate Certificate
     *
     * @param string $privateKey
     * @param string $publicKey
     * @return void
     */
    protected function activateCertificate(string $privateKey, string $publicKey): void
    {
        $certificateId = $this->getRegistrationResponseValue("payment/unzer_applepay/csr_certificate_response");

        $url = $this->getApiUrl(
            $privateKey,
            'v1/keypair/applepay/certificates/' . $certificateId . '/activate'
        );

        // post method
        $this->curl->post($url, []);

        // output of curl request
        $result = $this->curl->getBody();

        $result = (array)$this->serializer->unserialize($result);

        $this->handleResult($result, $publicKey);
    }
}
