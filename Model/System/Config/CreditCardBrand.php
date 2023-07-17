<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\System\Config;

class CreditCardBrand
{
    /**
     * Most used credit card types
     * @var array
     */
    public static array $baseCardTypes = [
        'MASTER' => 'MasterCard',
        'VISA' => 'Visa',
    ];

    /**
     * Get credit card brand by type
     *
     * @param string $type
     * @return string
     */
    public function getBrandByType(string $type): string
    {
        return self::$baseCardTypes[$type] ?? $type;
    }
}
