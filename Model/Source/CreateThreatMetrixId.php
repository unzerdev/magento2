<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Source;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;

/**
 *
 * @link  https://docs.unzer.com/
 */
class CreateThreatMetrixId
{
    /**
     * @var Random
     */
    private Random $mathRandom;

    /**
     * @var Encryptor
     */
    private Encryptor $encryptor;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * Constructor
     *
     * @param Random $mathRandom
     * @param Encryptor $encryptor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Random $mathRandom,
        Encryptor $encryptor,
        StoreManagerInterface $storeManager
    ) {
        $this->mathRandom = $mathRandom;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute
     *
     * @param Quote $quote
     * @return string|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Quote $quote): ?string
    {
        if ($quote->getId() === null) {
            return null;
        }

        $envPrefix = $this->createEnvPrefix();
        if ($envPrefix === null) {
            return null;
        }

        return $this->assembleThreatMetrixId($envPrefix, $quote->getId());
    }

    /**
     * Create Env Prefix
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    private function createEnvPrefix(): ?string
    {
        $baseUrl = $this->getBaseUrl();

        $baseUrl = $this->trimProtocolFromBaseUrl($baseUrl);

        return $this->removeNotAllowedCharsFromBaseUrl($baseUrl);
    }

    /**
     * Get Base Url
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getBaseUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Trim Protocol From Base Url
     *
     * @param string $baseUrl
     * @return string
     */
    private function trimProtocolFromBaseUrl(string $baseUrl): string
    {
        return str_replace(['https://', 'http://'], '', $baseUrl);
    }

    /**
     * Remove Not Allowed Chars From Base Url
     *
     * @param string $baseUrl
     * @return string|null
     */
    private function removeNotAllowedCharsFromBaseUrl(string $baseUrl): ?string
    {
        return preg_replace('/[^A-Za-z0-9\-]/', '', $baseUrl);
    }

    /**
     * Assemble Threat Metrix ID
     *
     * @param string $envPrefix
     * @param string $quoteId
     * @return string
     * @throws LocalizedException
     */
    private function assembleThreatMetrixId(string $envPrefix, string $quoteId): string
    {
        return $envPrefix . '_' . $this->createHashForQuote($quoteId);
    }

    /**
     * Create Hash For Quote
     *
     * @param string $quoteId
     * @return string
     * @throws LocalizedException
     */
    private function createHashForQuote(string $quoteId): string
    {
        $partsForHash = [
            $quoteId,
            $this->mathRandom->getUniqueHash(),
            microtime(true)
        ];
        return $this->encryptor->getHash(implode('', $partsForHash));
    }
}
