<?php

namespace Unzer\PAPI\Controller\Adminhtml\Webhooks;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Backend\App\Action;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as configCollection;
use UnzerSDK\Unzer;
use Magento\Backend\Model\Auth\Session;

/**
 * Controller for registering webhooks via the backend
 *
 * @link  https://docs.unzer.com/
 *
 *
 */
class RegisterApplepay extends Action
{

    private Curl $curl;
    private ScopeConfigInterface $_scopeConfig;
    private WriterInterface $_configWriter;
    private StoreManagerInterface $_storeManager;
    private configCollection $configCollection;


    public function __construct(
        Action\Context        $context,
        ScopeConfigInterface  $scopeConfig,
        Curl                  $curl,
        WriterInterface       $configWriter,
        StoreManagerInterface $storeManager,
        ConfigCollection      $configCollection,
        Session               $session
    )
    {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->_configWriter = $configWriter;
        $this->_storeManager = $storeManager;
        $this->configCollection = $configCollection;
        $this->_session = $session;
    }

    public function getApiUrl($logging, $url)
    {
        $envPrefix = 'sbx-';
        if (!$logging) {
            $envPrefix = '';
        }
        return "https://" . $envPrefix . Unzer::BASE_URL . "/" . $url;
    }

    /**
     * @inheritDoc
     */
    public function execute(): Redirect
    {
        $this->getRequest()->getParams();
        $mode = $this->getRequest()->getParam('switch');

        $unzerPrivateKey = base64_encode($this->_scopeConfig->getValue('payment/unzer/private_key') . ":");
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("Authorization", "Basic " . $unzerPrivateKey);
        $result = '';

        $sslCertPath = '../app/etc/upload/applepay_csr/' . $this->_scopeConfig->getValue('payment/unzer/applepay/csr_certificate_upload');
        $sslCert = file_get_contents($sslCertPath);
        $sslCert = str_replace('-----BEGIN CERTIFICATE-----', '', $sslCert);
        $sslCert = str_replace('-----END CERTIFICATE-----', '', $sslCert);
        $sslCert = str_replace("\n", "", $sslCert);

        $sslKeyPath = '../app/etc/upload/applepay_csr/' . $this->_scopeConfig->getValue('payment/unzer/applepay/csr_privat_key_upload');

        $sslKey = file_get_contents($sslKeyPath);
        $sslKey = str_replace('-----BEGIN PRIVATE KEY-----', '', $sslKey);
        $sslKey = str_replace('-----END PRIVATE KEY-----', '', $sslKey);
        $sslKey = str_replace("\n", "", $sslKey);

        switch ($mode) {

            case 'registerPrivateKey':
                $certificate = $sslKey;
                $url = $this->getApiUrl($this->_scopeConfig->getValue('payment/unzer/logging'), 'v1/keypair/applepay/privatekeys');

                $params = [
                    'format' => 'PEM',
                    'type' => 'private-key',
                    'certificate' => $certificate
                ];

                // post method
                $this->curl->post($url, json_encode($params));

                // output of curl requestt
                $result = $this->curl->getBody();

                $result = (array)json_decode($result);

                if (array_key_exists('errors', $result)) {
                    $errors = $result['errors'][0];
                    $errorMessage = $errors->code . " - " . $errors->merchantMessage;
                    $this->messageManager->addErrorMessage(__($errorMessage));
                } else {
                    $this->_configWriter->save('payment/unzer_applepay/csr_private_key_response', $result['id'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                    $this->messageManager->addSuccessMessage(__('Successfully registered'));
                }
                break;

            case 'registerCertificate':

                $certificate = $sslCert;
                //$privateKey = $this->_scopeConfig->getValue('payment/unzer_applepay/csr_private_key_response');
                $privateKey = $this->getRegistrationResponseValue("payment/unzer_applepay/csr_private_key_response");
                $url = $this->getApiUrl($this->_scopeConfig->getValue('payment/unzer/logging'), 'v1/keypair/applepay/certificates');

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

                if (array_key_exists('errors', $result)) {
                    $errors = $result['errors'][0];
                    $errorMessage = $errors->code . " - " . $errors->merchantMessage;
                    $this->messageManager->addErrorMessage(__($errorMessage));
                } else {
                    $this->_configWriter->save('payment/unzer_applepay/csr_certificate_response', $result['id'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                    $this->messageManager->addSuccessMessage(__('Successfully registered'));
                }
                break;

            case 'activate':
                $certificateId = $this->getRegistrationResponseValue("payment/unzer_applepay/csr_certificate_response");

                $url = $this->getApiUrl($this->_scopeConfig->getValue('payment/unzer/logging'), 'v1/keypair/applepay/certificates/' . $certificateId . '/activate');

                // post method
                $this->curl->post($url, []);

                // output of curl requestt
                $result = $this->curl->getBody();

                $result = (array)json_decode($result);

                if (array_key_exists('errors', $result)) {
                    $errors = $result['errors'][0];
                    $errorMessage = $errors->code . " - " . $errors->merchantMessage;
                    $this->messageManager->addErrorMessage(__($errorMessage));
                } else {
                    $this->_configWriter->save('payment/unzer_applepay/csr_certificate_response', $result['id'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                    $this->messageManager->addSuccessMessage(__('Successfully registered'));
                }
                break;
        }
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($this->getRequest()->getHeader('Referer').$this->getAnchorLink());
        return $redirect;
    }

    /**
     * @return string
     */
    public function getAnchorLink(){
        $anchor = "#payment_us_unzer_applepay-link";
        $configState = $this->_session->getUser()->getExtra();
        $configStateKeys = array_keys($configState["configState"]);
        $dataForAnchorGeneration = preg_grep('/.*unzer.*/', $configStateKeys);
        if(!empty($dataForAnchorGeneration)){
            $anchor = "#".$dataForAnchorGeneration[0]."_applepay-link";
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
}
