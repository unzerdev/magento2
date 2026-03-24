<?php

declare(strict_types=1);

namespace Unzer\PAPI\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateIdealTitle implements DataPatchInterface
{
    private const CONFIG_PATH = 'payment/unzer_ideal/title';

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
     * @return $this|UpdateIdealTitle
     */
    public function apply(): UpdateIdealTitle
    {
        $connection = $this->moduleDataSetup->getConnection();

        $connection->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['value' => 'iDEAL | Wero'],
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
