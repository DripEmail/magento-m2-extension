<?php

namespace Drip\TestUtils\Creators;

/**
 * Create bundle product for tests.
 */
class BundleProductCreator
{
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface **/
    protected $productRepository;

    /** @var \Drip\TestUtils\Creators\SimpleProductCreatorFactory **/
    protected $simpleProductCreatorFactory;

    /** @var \Magento\Bundle\Api\Data\LinkInterfaceFactory */
    protected $bundleLinkFactory;

    /** @var \Magento\Bundle\Api\Data\OptionInterfaceFactory */
    protected $bundleOptionFactory;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Drip\TestUtils\Creators\SimpleProductCreatorFactory $simpleProductCreatorFactory,
        \Magento\Bundle\Api\Data\LinkInterfaceFactory $bundleLinkFactory,
        \Magento\Bundle\Api\Data\OptionInterfaceFactory $bundleOptionFactory,
        $productData
    ) {
        $this->productRepository = $productRepository;
        $this->simpleProductCreatorFactory = $simpleProductCreatorFactory;
        $this->bundleLinkFactory = $bundleLinkFactory;
        $this->bundleOptionFactory = $bundleOptionFactory;
        $this->productData = $productData;
    }

    public function create()
    {
        $configuredBundleOptions = $this->productData['bundle_options'];
        unset($this->productData['bundle_options']);

        $bundleProduct = $this->simpleProductCreatorFactory->create(['productData' => $this->productData])->build();
        $bundleProduct->setStockData([
            'use_config_manage_stock' => 0, //'Use config settings' checkbox
            'manage_stock' => 1, //manage stock
            'is_in_stock' => 1, //Stock Availability
        ]);

        // Requires title
        $defaultOptions = [
            'delete' => '',
            'type' => 'select',
            'required' => '1'
        ];

        $extOptions = [];
        foreach ($configuredBundleOptions as $option) {
            $productOptions = $option['product_options'];
            unset($option['product_options']);

            $links = [];
            foreach ($productOptions as $productData) {
                $simpleProduct = $this->simpleProductCreatorFactory->create(['productData' => $productData])->build();
                $this->productRepository->save($simpleProduct);
                $bundleSelection = [
                    'product_id' => $simpleProduct->getId(),
                    'delete' => '',
                    'selection_price_value' => $simpleProduct->getPrice(),
                    'selection_qty' => 1,
                    'selection_can_change_qty' => 0
                ];

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
