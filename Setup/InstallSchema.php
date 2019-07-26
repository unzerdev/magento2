<?php

namespace Heidelpay\Gateway2\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend_Db_Exception;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @inheritDoc
     * @throws Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (!$setup->tableExists('heidelpay_gateway2_payment_information')) {
            $connection = $setup->getConnection();

            $tableName = $setup->getTable('heidelpay_gateway2_payment_information');

            $table = $connection->newTable($tableName)
                ->addColumn('id', Table::TYPE_INTEGER, 10, ['identity' => true, 'nullable' => false, 'primary' => true])
                ->addColumn('external_id', Table::TYPE_TEXT, 255, ['nullable' => false])
                ->addColumn('payment_id', Table::TYPE_TEXT, 255, ['nullable' => false])
                ->addColumn('order_id', Table::TYPE_INTEGER, 10, ['nullable' => true, 'unsigned' => true])
                ->addColumn('order_increment_id', Table::TYPE_TEXT, 32, ['nullable' => false])
                ->addColumn('redirect_url', Table::TYPE_TEXT, 255, ['nullable' => true]);

            $connection->createTable($table);

            $connection->addForeignKey(
                $setup->getFkName($tableName, 'order_id', $setup->getTable('sales_order'), 'entity_id'),
                $tableName,
                'order_id',
                $setup->getTable('sales_order'),
                'entity_id'
            );
        }

        $setup->endSetup();
    }
}