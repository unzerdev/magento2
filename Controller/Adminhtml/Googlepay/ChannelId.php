<?php
declare(strict_types=1);

namespace Unzer\Papi\Controller\Adminhtml\Googlepay;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use Unzer\PAPI\Model\Config;

class ChannelId implements HttpGetActionInterface
{
    /** @var Config */
    private Config $configHelper;

    /** @var JsonFactory */
    private JsonFactory $jsonFactory;

    /** @var RequestInterface */
    private RequestInterface $request;

    /**
     * Index constructor.
     *
     * @param Config $configHelper
     * @param JsonFactory $jsonFactory
     * @param RequestInterface $request
     */
    public function __construct(
        Config $configHelper,
        JsonFactory $jsonFactory,
        RequestInterface $request
    ) {
        $this->configHelper = $configHelper;
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
    }

    /**
     * Controller execution.
     *
     * @return Json
     * @throws UnzerApiException
     */
    public function execute(): Json
    {
        $json = $this->jsonFactory->create();
        $data = [
            'channel_id' => $this->fetchChannelId(),
        ];
        $json->setData($data);

        return $json;
    }

    /**
     * Fetch Channel ID
     *
     * @return string
     * @throws UnzerApiException
     */
    private function fetchChannelId(): string
    {
        $storeId = (string) $this->request->getParam('store', 0);

        $keyPair = $this->configHelper->getUnzerClient($storeId)->getResourceService()->fetchKeypair(true);

        foreach ($keyPair->getPaymentTypes() as $paymentType) {
            if (!property_exists($paymentType, 'type')) {
                continue;
            }
            if ($paymentType->type === 'googlepay') {
                if (!property_exists($paymentType, 'supports')) {
                    return '';
                }
                if (!is_array($paymentType->supports) || !array_key_exists(0, $paymentType->supports)) {
                    return '';
                }
                if (!property_exists($paymentType->supports[0], 'channel')) {
                    return '';
                }

                return $paymentType->supports[0]->channel;
            }
        }

        return '';
    }
}
