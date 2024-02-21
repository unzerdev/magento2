<?php

namespace Unzer\PAPI\Controller\Adminhtml\Webhooks;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Backend\App\Action;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as configCollection;
use UnzerSDK\Services\EnvironmentService;
use UnzerSDK\Unzer;
use UnzerSDK\Services\HttpService;
use Magento\Backend\Model\Auth\Session;

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


    private Curl $curl;
    private ScopeConfigInterface $_scopeConfig;
    private WriterInterface $_configWriter;
    private configCollection $configCollection;
    private HttpService $httpService;


    public function __construct(
        Action\Context       $context,
        ScopeConfigInterface $scopeConfig,
        Curl                 $curl,
        WriterInterface      $configWriter,
        ConfigCollection     $configCollection,
        HttpService          $httpService,
        Session              $session
    )
    {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->_configWriter = $configWriter;
        $this->configCollection = $configCollection;
        $this->httpService = $httpService;
        $this->_session = $session;
    }


    /**
     * @inheritDoc
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

        $sslCertPath = '../app/etc/upload/applepay_csr/' . $this->_scopeConfig->getValue('payment/unzer/applepay/csr_certificate_upload', 'store', $storeId);
        $sslCert = file_get_contents($sslCertPath);
        $sslCert = str_replace('-----BEGIN CERTIFICATE-----', '', $sslCert);
        $sslCert = str_replace('-----END CERTIFICATE-----', '', $sslCert);
        $sslCert = str_replace("\n", "", $sslCert);

        $sslKeyPath = '../app/etc/upload/applepay_csr/' . $this->_scopeConfig->getValue('payment/unzer/applepay/csr_privat_key_upload', 'store', $storeId);

        $sslKey = file_get_contents($sslKeyPath);
        $sslKey = str_replace('-----BEGIN PRIVATE KEY-----', '', $sslKey);
        $sslKey = str_replace('-----END PRIVATE KEY-----', '', $sslKey);
        $sslKey = str_replace("\n", "", $sslKey);


        switch ($mode) {

            case 'registerPrivateKey':
                $certificate = $sslKey;
                $url = $this->getApiUrl($privateKey, 'v1/keypair/applepay/privatekeys');

                $params = [
                    'format' => 'PEM',
                    'type' => 'private-key',
                    'certificate' => $certificate
                ];

                // post method
                $this->curl->post($url, json_encode($params));

                // output of curl request
                $result = $this->curl->getBody();

                $result = (array)json_decode($result);

                if (array_key_exists('errors', $result)) {
                    $errors = $result['errors'][0];
                    $errorMessage = $errors->code . " - " . $errors->merchantMessage;
                    $this->messageManager->addErrorMessage(__($errorMessage));
                } else {
                    $this->_configWriter->save('payment/unzer_applepay/csr_private_key_response', $result['id'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                    $this->messageManager->addSuccessMessage(__('Successfully registered for public key: ' . $publicKey));
                }
                break;

            case 'registerCertificate':

                $certificate = $sslCert;
                $privateKey = $this->getRegistrationResponseValue("payment/unzer_applepay/csr_private_key_response");
                $url = $this->getApiUrl($privateKey, 'v1/keypair/applepay/certificates');

                $params = [
                    'format' => 'PEM',
                    'type' => 'certificate',
                    'private-key' => $privateKey,
                    'certificate' => $certificate
                ];

                // post method
                $this->curl->post($url, json_encode($params));

                // output of curl requestt
                $result = $this->curl->getBody();

                $result = (array)json_decode($result);

                $this->handleResult($result,$publicKey);
                break;

            case 'activate':
                $certificateId = $this->getRegistrationResponseValue("payment/unzer_applepay/csr_certificate_response");

                $url = $this->getApiUrl($privateKey, 'v1/keypair/applepay/certificates/' . $certificateId . '/activate');

                // post method
                $this->curl->post($url, []);

                // output of curl requestt
                $result = $this->curl->getBody();

                $result = (array)json_decode($result);

                $this->handleResult($result,$publicKey);
                break;
        }
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($this->getRequest()->getHeader('Referer') . $this->getAnchorLink());
        return $redirect;
    }

    /**
     * @param $result
     * @param $publicKey
     * @return void
     */
    public function handleResult($result,$publicKey){
        if (array_key_exists('errors', $result)) {
            $errors = $result['errors'][0];
            $errorMessage = $errors->code . " - " . $errors->merchantMessage;
            $this->messageManager->addErrorMessage(__($errorMessage));
        } else {
            $this->_configWriter->save('payment/unzer_applepay/csr_certificate_response', $result['id'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
            $this->messageManager->addSuccessMessage(__('Successfully registered for public key: ' . $publicKey));
        }
    }
    /**
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
            $anchor = "#" . $dataForAnchorGeneration[0] . "_applepay-link";
        }
        return $anchor;
    }

    /**
     * @return mixed|string
     */
    public function getRegistrationResponseValue($path)
    {
        $collection = $this->configCollection->create();
        $collection->addFieldToFilter("path", ['eq' => $path]);
        if ($collection->count() > 0) {
            return $collection->getFirstItem()->getData()['value'];
        } else {
            return "";
        }

    }

    /**
     * @param $privateKey
     * @param $url
     * @return string
     */
    public function getApiUrl($privateKey, $url): string
    {
        $envPrefix = $this->getEnvironmentPrefix($privateKey);
        return "https://" . $envPrefix . Unzer::BASE_URL . "/" . $url;
    }

    /**
     * @param $privateKey
     * @return string
     */
    function getEnvironmentPrefix($privateKey): string
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

    /** Determine whether key is for production environment.
     *
     * @param string $privateKey
     *
     * @return bool
     */
    private function isProductionKey(string $privateKey): bool
    {
        return strpos($privateKey, 'p') === 0;
    }
}
