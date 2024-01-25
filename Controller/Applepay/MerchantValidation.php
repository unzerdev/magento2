<?php
namespace Unzer\PAPI\Controller\Applepay;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Apple Pay Merchant Validation Controller
 *
 * @link  https://docs.unzer.com/
 */
class MerchantValidation extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute()
    {
        header("Content-type: application/json; charset=utf-8");

        // POST from applepay.js jQuery.post()
        $jsonData = json_decode(file_get_contents('php://input'), true);
        $postValidationUrl = $this->getPostValidationUrl((array)$jsonData);
        $storeId =  $this->getStoreId((array)$jsonData);

        $merchantIdentifier = $this->scopeConfig->getValue('payment/unzer_applepay/apple_pay_merchant_id', 'store', $storeId);
        $displayName = $this->scopeConfig->getValue('payment/unzer_applepay/display_name', 'store', $storeId);
        $domainName = $this->scopeConfig->getValue('payment/unzer_applepay/domain_name', 'store', $storeId);

        $sslCert = '../app/etc/upload/applepay/' . $this->scopeConfig->getValue('payment/unzer/applepay/certificate_file', 'store', $storeId);
        $sslKey = '../app/etc/upload/applepay/' . $this->scopeConfig->getValue('payment/unzer/applepay/private_key_file', 'store', $storeId);

        $applepaySession = new ApplepaySession($merchantIdentifier, $displayName, $domainName);

        $appleAdapter = new ApplepayAdapter();
        $appleAdapter->init($sslCert, $sslKey);

        try {
            $validationUrl = $postValidationUrl;
            $validationResponse = $appleAdapter->validateApplePayMerchant($validationUrl, $applepaySession);
            echo $validationResponse;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        exit();
    }

    /**
     * @param array $data
     * @return string
     */
    public function getPostValidationUrl(array $data): string {
        return $data['merchantValidationUrl'] ?? '';
    }


    /**
     * @param array $data
     * @return string
     */
    public function getStoreId(array $data): string {
        return $data['storeId'] ?? '0';
    }

}
