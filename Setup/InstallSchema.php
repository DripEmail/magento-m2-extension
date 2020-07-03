<?php

namespace Drip\Connect\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Install schema
 */
class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $eavTable = $installer->getTable('quote');

        $columns = [
            'drip' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'visible' => false,
                'required' => false,
                'comment' => 'tracks if checkout created event has been sent',
            ],
        ];

        $connection = $installer->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($eavTable, $name, $definition);
        }

        $installer->endSetup();
    }
}
