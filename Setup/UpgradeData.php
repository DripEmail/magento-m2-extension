<?php

namespace Drip\Connect\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Model\Customer;

class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    protected $setup;

    /** @var \Magento\Framework\Setup\ModuleContextInterface */
    protected $context;

    public function __construct(
        \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->setup = $setup;
        $this->context = $context;

        if (version_compare($context->getVersion(), '0.2.0') < 0) {
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

            $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $attributeCode = 'drip';
            $attributeLabel = 'Drip';

            $attributeId = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode)->getId();
            if (!empty($attributeId)) {
                $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode)->delete();
            }

            $customerSetup->addAttribute(
                Customer::ENTITY,
                $attributeCode,
                [
                    'label' => $attributeLabel,
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'input' => 'select',
                    'required' => false,
                    'visible' => false,
                    'default' => 0,
                    'user_defined' => true,
                    'sort_order' => 1000,
                    'position' => 1000,
                    'system' => 0,
                ]
            );

            $attribute = $customerSetup->getEavConfig()->clear()->getAttribute(Customer::ENTITY, $attributeCode);
            $attribute
                ->addData(
                    [
                        'attribute_set_id' => $attributeSetId,
                        'attribute_group_id' => $attributeGroupId,
                        'used_in_forms' => ['adminhtml_customer', 'customer_account_create'],
                    ]
                );
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.5.1') < 0) {
            $this->updateCustomerDripAttribute();
        }

        $setup->endSetup();
    }

    /**
     * hide Drip attribute on customer create form
     */
    protected function updateCustomerDripAttribute()
    {
        $attributeCode = 'drip';

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode);
        if (! empty($attribute->getId())) {
            $attribute
                ->addData(
                    [
                        'used_in_forms' => ['adminhtml_customer'],
                    ]
                );
            $attribute->save();
        }
    }
}
