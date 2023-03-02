<?php declare(strict_types=1);

namespace Unzer\PAPI\Model\Method;

interface OverrideApiCredentialInterface
{

    public function hasMethodValidOverrideKeys(string $storeId = null): bool;

    public function getMethodOverridePublicKey(string $storeId = null): string;

    public function getMethodOverridePrivateKey(string $storeId = null): string;
}
