<?php

namespace Drip\TestUtils\Creators;

class ConfigurableProductCreator
{
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface **/
    protected $productRepository;

    /** @var \Drip\TestUtils\Creators\SimpleProductCreatorFactory **/
    protected $simpleProductCreatorFactory;

    /** @var \Magento\Eav\Model\Config */
    protected $eavConfig;

    /** @var \Magento\Eav\Setup\EavSetupFactory */
    protected $eavSetupFactory;

    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface **/
    protected $setup;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Drip\TestUtils\Creators\SimpleProductCreatorFactory $simpleProductCreatorFactory,
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        $productData
    ) {
        $this->productRepository = $productRepository;
        $this->simpleProductCreatorFactory = $simpleProductCreatorFactory;
        $this->setup = $setup;
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->productData = $productData;
    }

    public function create()
    {
        $attributes = $this->productData['attributes'];
        unset($this->productData['attributes']);

        $configProduct = $this->simpleProductCreatorFactory->create(['productData' => $this->productData])->build();
        $configProduct->setStockData(array(
            'use_config_manage_stock' => 0, //'Use config settings' checkbox
            'manage_stock' => 1, //manage stock
            'is_in_stock' => 1, //Stock Availability
        ));

        $attributeIds = array();
        $configurableAttributesData = array();
        // $configurableProductsData = array();
        $associatedProductIds = array();

        foreach ($attributes as $attrName => $attrValues) {
            $attribute = $this->buildAttribute($attrName, array_keys($attrValues));
            $attributeIds[] = $attribute->getId();

            $attributeValues = array();

            foreach ($attrValues as $option => $simpleProductData) {
                $simpleProduct = $this->simpleProductCreatorFactory->create(['productData' => $simpleProductData])->build();
                $simpleProduct->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
                $optionId = $attribute->setStoreId(0)->getSource()->getOptionId($option);
                $simpleProduct->setData($attrName, $optionId);
                $simpleProduct = $this->productRepository->save($simpleProduct);
                $associatedProductIds[] = $simpleProduct->getId();

                $attributeValues[] = [
                    'label' => $option,
                    'attribute_id' => $attribute->getId(),
                    'value_index' => $optionId,
                ];
            }

            $configurableAttributesData[] = array(
                'attribute_id' => $attribute->getId(),
                'code' => $attribute->getAttributeCode(),
                'label' => $attribute->getStoreLabel(),
                'position' => '0',
                'values' => $attributeValues,
            );
        }


        // All the documentation says not to use object manager. But not using object manager breaks in weird ways.
        // This is test harness code. I don't care.
        $ob = \Magento\Framework\App\ObjectManager::getInstance();
        $optionsFactory = $ob->create(\Magento\ConfigurableProduct\Helper\Product\Options\Factory::class);

        $configurableOptions = $optionsFactory->create($configurableAttributesData);

        $extensionConfigurableAttributes = $configProduct->getExtensionAttributes();
        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
        $configProduct->setExtensionAttributes($extensionConfigurableAttributes);

        $this->productRepository->save($configProduct);
    }

    protected function buildAttribute($title, $options)
    {
        $this->setup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);

        $eavSetup->addAttribute('catalog_product', $title, array(
            'group' => 'General',
            'label' => $title,
            'input' => 'select',
            'type' => 'varchar',
            'required' => 0,
            'visible_on_front' => false,
            'filterable' => 0,
            'filterable_in_search' => 0,
            'searchable' => 0,
            'used_in_product_listing' => true,
            'visible_in_advanced_search' => false,
            'comparable' => 0,
            'user_defined' => 1,
            'is_configurable' => 0,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
            'option' => array('values' => $options),
            'note' => '',
        ));

        $this->setup->endSetup();

        // Obtain and return the attribute.
        return $this->eavConfig->getAttribute('catalog_product', $title);
    }
}
