<?php
declare(strict_types=1);

namespace Unzer\PAPI\Helper;

use Magento\Framework\Url;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Helper for webhook handling
 *
 * @link  https://docs.unzer.com/
 */
class Webhooks
{
    public const URL_PARAM_STORE = 'store';

    /**
     * @var Url
     */
    protected Url $_urlBuilder;

    /**
     * Webhooks constructor.
     * @param Url $urlBuilder
     */
    public function __construct(Url $urlBuilder)
    {
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Get Url
     *
     * @param StoreInterface $store
     * @return string
     */
    public function getUrl(StoreInterface $store): string
    {
        return $this->_urlBuilder
            ->setScope($store)
            ->getUrl('unzer/webhooks/process', ['_nosid' => true, self::URL_PARAM_STORE => $store->getId()]);
    }
}
