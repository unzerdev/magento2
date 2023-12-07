<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

/**
 * @link  https://docs.unzer.com/
 */
interface OverrideApiCredentialInterface
{

    /**
     * Has Method Valid Override Keys
     *
     * @param string|null $storeId
     * @return bool
     */
    public function hasMethodValidOverrideKeys(string $storeId = null): bool;

    /**
     * Get Method Override Public Key
     *
     * @param string|null $storeId
     * @return string
     */
    public function getMethodOverridePublicKey(string $storeId = null): string;

    /**
     * Get Method Override Private Key
     *
     * @param string|null $storeId
     * @return string
     */
    public function getMethodOverridePrivateKey(string $storeId = null): string;
}
