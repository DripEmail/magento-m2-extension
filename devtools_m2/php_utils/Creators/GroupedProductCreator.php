<?php

namespace Drip\TestUtils\Creators;

/**
 * Create grouped product for tests
 */
class GroupedProductCreator
{
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface **/
    protected $productRepository;

    /** @var \Drip\TestUtils\Creators\SimpleProductCreatorFactory **/
    protected $simpleProductCreatorFactory;

    /** @var \Magento\Catalog\Api\Data\ProductLinkInterface */
    protected $productLinkFactory;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Drip\TestUtils\Creators\SimpleProductCreatorFactory $simpleProductCreatorFactory,
        \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory,
        $productData
    ) {
        $this->productRepository = $productRepository;
        $this->simpleProductCreatorFactory = $simpleProductCreatorFactory;
        $this->productLinkFactory = $productLinkFactory;
        $this->productData = $productData;
    }

    public function create()
    {
        $associated = $this->productData['associated'];
        unset($this->productData['associated']);

        $groupedProduct = $this->simpleProductCreatorFactory->create(['productData' => $this->productData])->build();
        $groupedProduct->setStockData([
            'use_config_manage_stock' => 0, //'Use config settings' checkbox
            'manage_stock' => 1, //manage stock
            'is_in_stock' => 1, //Stock Availability
        ]);
        // This save mostly happens in order to keep the product creation in
        // the same order as the M1 test suite. This makes things more
        // consistent.
        $this->productRepository->save($groupedProduct);

        $productLinks = [];

        foreach ($associated as $simpleProductData) {
            $simpleProduct = $this->simpleProductCreatorFactory->create(['productData' => $simpleProductData])->build();
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
}
