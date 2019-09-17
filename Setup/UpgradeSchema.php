<?php

namespace Drip\Connect\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Upgrade schema for extension
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /** @var ModuleContextInterface $context */
    protected $context;

    /** @var SchemaSetupInterface $setup */
    protected $setup;

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $this->setup = $setup;
        $this->context = $context;

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->addDripColumnToSubscribers();
        }

        $installer->endSetup();
    }

    protected function addDripColumnToSubscribers()
    {
        $table = $this->setup->getTable('newsletter_subscriber');

        $columns = [
            'drip' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'visible' => false,
                'required' => false,
                'comment' => 'tracks if subscriber created event has already been sent',
            ],
        ];

        $connection = $this->setup->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($table, $name, $definition);
        }
    }
}
