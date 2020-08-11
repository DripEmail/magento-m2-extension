<?php

namespace Drip\Connect\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Model\Customer;

/**
 * Upgrade data during version updates.
 */
class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    protected $setup;

    /** @var \Magento\Framework\Setup\ModuleContextInterface */
    protected $context;

    /** @var \Magento\Config\Model\ResourceModel\Config */
    protected $resourceConfig;

    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory
    ) {
        $this->configFactory = $configFactory;
        $this->resourceConfig = $resourceConfig;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->setup = $setup;
        $this->context = $context;

        $setup->endSetup();
    }
}
