<?php

namespace Drip\TestUtils\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateProductCommand extends Command
{
    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $catalogProductFactory;

    /** @var \Magento\Framework\App\State **/
    private $state;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct();

        $this->catalogProductFactory = $catalogProductFactory;
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
                $this->buildSimpleProduct($json)->save();
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
        $installer = new Mage_Eav_Model_Entity_Setup('core_setup');
        $installer->startSetup();
        $installer->addAttribute('catalog_product', $title, array(
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
            'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'option' => array('values' => $options),
            'note' => '',
        ));

        $installer->endSetup();

        // Obtain and return the attribute.
        return Mage::getModel('eav/config')->getAttribute('catalog_product', $title);
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
        $configurableProductsData = array();

        foreach ($attributes as $attrName => $attrValues) {
            $attribute = $this->buildAttribute($attrName, array_keys($attrValues));
            $attributeIds[] = $attribute->getId();

            foreach ($attrValues as $option => $simpleProductData) {
                $simpleProduct = $this->buildSimpleProduct($simpleProductData);
                $optionId = $attribute->setStoreId(0)->getSource()->getOptionId($option);
                $simpleProduct->setData($attrName, $optionId);
                $simpleProduct->save();

                $configurableProductsData[$simpleProduct->getId()][] = array(
                    'label' => $option,
                    'attribute_id' => $attribute->getId(),
                    'value_index' => $optionId,
                    'is_percent' => '0', //fixed/percent price for this option
                    'pricing_value' => $simpleProduct->getPrice()
                );
            }
        }

        // Set attribute data
        $configProduct->getTypeInstance()->setUsedProductAttributeIds($attributeIds);
        $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
        $configProduct->setCanSaveConfigurableAttributes(true);
        $configProduct->setConfigurableAttributesData($configurableAttributesData);
        $configProduct->setConfigurableProductsData($configurableProductsData);

        $configProduct->save();
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
        $groupedProduct->save();

        $products_links = Mage::getModel('catalog/product_link_api');

        foreach ($associated as $simpleProductData) {
            $simpleProduct = $this->buildSimpleProduct($simpleProductData);
            $simpleProduct->save();
            $products_links->assign("grouped", $groupedProduct->getId(), $simpleProduct->getId());
        }
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
            'option_id' => '',
            'delete' => '',
            'type' => 'select',
            'required' => '1',
            'position' => '1'
        );

        $bundleOptions = array();
        $bundleSelections = array();
        foreach ($configuredBundleOptions as $option) {
            $productOptions = $option['product_options'];
            unset($option['product_options']);

            $selections = array();
            foreach ($productOptions as $productData) {
                $simpleProduct = $this->buildSimpleProduct($productData);
                $simpleProduct->save();
                $selections[] = array(
                    'product_id' => $simpleProduct->getId(),
                    'delete' => '',
                    'selection_price_value' => $simpleProduct->getPrice(),
                    'selection_price_type' => 0,
                    'selection_qty' => 1,
                    'selection_can_change_qty' => 0,
                    'position' => 0,
                    'is_default' => 1
                );
            }

            // $bundleOptions and $bundleSelections need to have parallel indicies.
            $bundleOptions[] = array_replace_recursive($defaultOptions, $option);
            $bundleSelections[] = $selections;
        }

        //flags for saving custom options/selections
        $bundleProduct->setCanSaveCustomOptions(true);
        $bundleProduct->setCanSaveBundleSelections(true);
        $bundleProduct->setAffectBundleProductSelections(true);

        //registering a product because of Mage_Bundle_Model_Selection::_beforeSave
        Mage::register('product', $bundleProduct);

        //setting the bundle options and selection data
        $bundleProduct->setBundleOptionsData($bundleOptions);
        $bundleProduct->setBundleSelectionsData($bundleSelections);

        $bundleProduct->save();
    }
}
