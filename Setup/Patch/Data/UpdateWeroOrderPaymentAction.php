<?php

declare(strict_types=1);

namespace Unzer\PAPI\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateWeroOrderPaymentAction implements DataPatchInterface
{
    private const CONFIG_PATH = 'payment/unzer_wero/order_payment_action';

    /** @var ModuleDataSetupInterface  */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @return $this|UpdateWeroOrderPaymentAction
     */
    public function apply(): UpdateWeroOrderPaymentAction
    {
        $connection = $this->moduleDataSetup->getConnection();

        $connection->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['value' => 'authorize_capture'],
            ['path = ?' => self::CONFIG_PATH]
        );

        return $this;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
