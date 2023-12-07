<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Observer for assigning additional payment information from the frontend to payments
 *
 * @link  https://docs.unzer.com/
 */
class BaseDataAssignObserver extends AbstractDataAssignObserver
{
    public const KEY_CUSTOMER_ID = 'customer_id';
    public const KEY_RESOURCE_ID = 'resource_id';
    public const KEY_THREAT_METRIX_ID = 'threat_metrix_id';
    public const KEY_BIRTHDATE = 'birthDate';
    public const KEY_SALUTATION = 'salutation';

    /**
     * @var array
     */
    protected array $additionalInformationList = [
        self::KEY_CUSTOMER_ID,
        self::KEY_RESOURCE_ID,
        self::KEY_BIRTHDATE,
        self::KEY_SALUTATION,
        self::KEY_THREAT_METRIX_ID,
    ];

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);

        /** @var array $additionalData */
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
