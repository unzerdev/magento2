<?php
declare(strict_types=1);

namespace Unzer\PAPI\Api\Data;

/**
 * Create Risk Data Interface
 *
 * @link  https://docs.unzer.com/
 * @api
 */
interface CreateRiskDataInterface
{

    public const CUSTOMER_GROUP_TOP = 'TOP';
    public const CUSTOMER_GROUP_GOOD = 'GOOD';
    public const CUSTOMER_GROUP_BAD = 'BAD';
    public const CUSTOMER_GROUP_NEUTRAL = 'NEUTRAL';
    public const REGISTRATION_LEVEL_GUEST = '0';
    public const REGISTRATION_LEVEL_CUSTOMER = '1';

    /**
     * Create Unzer Risk Data Object and assign data to it
     *
     * @return \UnzerSDK\Resources\EmbeddedResources\RiskData|null
     * @see \Unzer\PAPI\Model\Checkout\Data\CreateRiskdata
     *
     */
    public function execute(): ?\UnzerSDK\Resources\EmbeddedResources\RiskData;
}
