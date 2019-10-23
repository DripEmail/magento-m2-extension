<?php

namespace Drip\TestUtils\Creators;

class SimpleProductCreator
{
    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $catalogProductFactory;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface **/
    protected $productRepository;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        $productData
    ) {
        $this->catalogProductFactory = $catalogProductFactory;
        $this->productRepository = $productRepository;
        $this->productData = $productData;
    }

    public function create()
    {
        $this->productRepository->save($this->build());
    }

    public function build()
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
        $fullData = array_replace_recursive($defaults, $this->productData);

        // This assumes that you properly name all of the attributes. But we control both ends, so it should be fine.
        foreach ($fullData as $key => $value) {
            $methodName = "set".ucfirst($key);
            $product->$methodName($value);
        }

        $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH); //catalog and search visibility

        return $product;
    }
}
