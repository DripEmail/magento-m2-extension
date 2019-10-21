<?php

namespace Drip\TestUtils\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateProductCommand extends Command
{
    /** @var \Magento\Bundle\Api\Data\LinkInterfaceFactory */
    protected $bundleLinkFactory;

    /** @var \Magento\Bundle\Api\Data\OptionInterfaceFactory */
    protected $bundleOptionFactory;

    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $catalogProductFactory;

    /** @var \Magento\Eav\Model\Config */
    protected $eavConfig;

    /** @var \Magento\Eav\Setup\EavSetupFactory */
    protected $eavSetupFactory;

    /** @var \Magento\Catalog\Api\Data\ProductLinkInterface */
    protected $productLinkFactory;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface **/
    protected $productRepository;

    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface **/
    protected $setup;

    /** @var \Magento\Framework\App\State **/
    protected $state;

    public function __construct(
        \Magento\Bundle\Api\Data\LinkInterfaceFactory $bundleLinkFactory,
        \Magento\Bundle\Api\Data\OptionInterfaceFactory $bundleOptionFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct();

        $this->bundleLinkFactory = $bundleLinkFactory;
        $this->bundleOptionFactory = $bundleOptionFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->productLinkFactory = $productLinkFactory;
        $this->productRepository = $productRepository;
        $this->setup = $setup;
        $this->state = $state;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('drip_testutils:createproduct')->setDescription('Create product using JSON from stdin');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Some bookkeeping
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

        $stdin = fopen('php://stdin', 'r');
        $data = stream_get_contents($stdin);
        $json = json_decode($data, true);

        if ($json === null) {
            throw new \Exception('Null JSON parse');
        }

        $type = array_key_exists('typeId', $json) ? $json['typeId'] : '';
        switch ($type) {
            case 'simple':
            case '':
            case null:
                $this->productRepository->save($this->buildSimpleProduct($json));
                break;
            case 'configurable':
                $this->buildConfigurableProduct($json);
                break;
            case 'grouped':
                $this->buildGroupedProduct($json);
                break;
            case 'bundle':
                $this->buildBundleProduct($json);
                break;
            default:
                throw new \Exception("Unsupported type: ${type}");
        }
    }

    protected function buildSimpleProduct($data)
    {
        $product = $this->catalogProductFactory->create();

        $defaultAttrSetId = $product->getDefaultAttributeSetId();

        $defaults = array(
            "storeId" => 1,
            "websiteIds" => [1],
            "typeId" => "simple",
            "weight" => 4.0000,
            "status" => 1, //product status (1 - enabled, 2 - disabled)
            "taxClassId" => 0, //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
            "price" => 11.22,
            "cost" => 22.33,
            "attributeSetId" => $defaultAttrSetId,
            "createdAt" => strtotime('now'),
            "updatedAt" => strtotime('now'),
            "stockData" => array(
                "use_config_manage_stock" => 0,
                "manage_stock" => 1,
                "is_in_stock" => 1,
                "qty" => 999
            ),
        );
        $fullData = array_replace_recursive($defaults, $data);

        // This assumes that you properly name all of the attributes. But we control both ends, so it should be fine.
        foreach ($fullData as $key => $value) {
            $methodName = "set".ucfirst($key);
            $product->$methodName($value);
        }

        $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH); //catalog and search visibility

        return $product;
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

    protected function buildConfigurableProduct($data)
    {
        $attributes = $data['attributes'];
        unset($data['attributes']);

        $configProduct = $this->buildSimpleProduct($data);
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
                $simpleProduct = $this->buildSimpleProduct($simpleProductData);
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

    protected function buildGroupedProduct($data)
    {
        $associated = $data['associated'];
        unset($data['associated']);

        $groupedProduct = $this->buildSimpleProduct($data);
        $groupedProduct->setStockData(array(
            'use_config_manage_stock' => 0, //'Use config settings' checkbox
            'manage_stock' => 1, //manage stock
            'is_in_stock' => 1, //Stock Availability
        ));
        // This save mostly happens in order to keep the product creation in
        // the same order as the M1 test suite. This makes things more
        // consistent.
        $this->productRepository->save($groupedProduct);

        $productLinks = array();

        foreach ($associated as $simpleProductData) {
            $simpleProduct = $this->buildSimpleProduct($simpleProductData);
            $this->productRepository->save($simpleProduct);

            $productLink = $this->productLinkFactory->create();

            $productLink->setSku($groupedProduct->getSku())
                ->setLinkType('associated')
                ->setLinkedProductSku($simpleProduct->getSku())
                ->setLinkedProductType($simpleProduct->getTypeId())
                ->getExtensionAttributes()
                ->setQty(0);

            $productLinks[] = $productLink;
        }

        $groupedProduct->setProductLinks($productLinks);

        $this->productRepository->save($groupedProduct);
    }

    protected function buildBundleProduct($data)
    {
        $configuredBundleOptions = $data['bundle_options'];
        unset($data['bundle_options']);

        $bundleProduct = $this->buildSimpleProduct($data);
        $bundleProduct->setStockData(array(
            'use_config_manage_stock' => 0, //'Use config settings' checkbox
            'manage_stock' => 1, //manage stock
            'is_in_stock' => 1, //Stock Availability
        ));

        // Requires title
        $defaultOptions = array(
            'delete' => '',
            'type' => 'select',
            'required' => '1'
        );

        $extOptions = [];
        foreach ($configuredBundleOptions as $option) {
            $productOptions = $option['product_options'];
            unset($option['product_options']);

            $links = array();
            foreach ($productOptions as $productData) {
                $simpleProduct = $this->buildSimpleProduct($productData);
                $this->productRepository->save($simpleProduct);
                $bundleSelection = array(
                    'product_id' => $simpleProduct->getId(),
                    'delete' => '',
                    'selection_price_value' => $simpleProduct->getPrice(),
                    'selection_qty' => 1,
                    'selection_can_change_qty' => 0
                );

                /** @var \Magento\Bundle\Api\Data\LinkInterface $link */
                $link = $this->bundleLinkFactory->create(['data' => $bundleSelection]);
                $link->setSku($simpleProduct->getSku());
                $link->setQty($bundleSelection['selection_qty']);
                $link->setPrice($bundleSelection['selection_price_value']);
                if (isset($bundleSelection['selection_can_change_qty'])) {
                    $link->setCanChangeQuantity($bundleSelection['selection_can_change_qty']);
                }
                $links[] = $link;
            }

            $optionData = array_replace_recursive($defaultOptions, $option);

            $extOption = $this->bundleOptionFactory->create(['data' => $optionData]);
            $extOption->setSku($bundleProduct->getSku());
            $extOption->setOptionId(null);
            $extOption->setProductLinks($links);

            $extOptions[] = $extOption;
        }

        $extension = $bundleProduct->getExtensionAttributes();
        $extension->setBundleProductOptions($extOptions);
        $bundleProduct->setExtensionAttributes($extension);

        $bundleProduct->setPriceView(1);

        $this->productRepository->save($bundleProduct);
    }
}
