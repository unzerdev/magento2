<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Resource;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;

/**
 *
 * Copyright (C) 2021 - today Unzer GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.unzer.com/
 *
 * @package  unzerdev/magento2
 */
class CreateThreatMetrixId
{
    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @throws LocalizedException
     */
    public function execute(Quote $quote): ?string
    {
        if (is_null($quote->getId())) {
            return null;
        }

        $envPrefix = $this->createEnvPrefix();
        if (is_null($envPrefix)) {
            return null;
        }

        return $this->assembleThreatMetrixId($envPrefix, $quote->getId());
    }

    /**
     * @throws NoSuchEntityException
     */
    private function createEnvPrefix(): ?string
    {
        $baseUrl = $this->getBaseUrl();

        $baseUrl = $this->trimProtocolFromBaseUrl($baseUrl);

        return $this->removeNotAllowedCharsFromBaseUrl($baseUrl);
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getBaseUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
    }

    private function trimProtocolFromBaseUrl(string $baseUrl): string
    {
        return str_replace(['https://', 'http://'], '', $baseUrl);
    }

    private function removeNotAllowedCharsFromBaseUrl(string $baseUrl): ?string
    {
        return preg_replace('/[^A-Za-z0-9\-]/', '', $baseUrl);
    }

    /**
     * @throws LocalizedException
     */
    private function assembleThreatMetrixId(string $envPrefix, string $quoteId): string
    {
        return $envPrefix . '_' . $this->createHashForQuote($quoteId);
    }

    /**
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
