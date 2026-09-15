<?php

namespace Drip\Connect\Api;

/**
 * Product details REST API endpoint.
 */
class ProductDetails
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $catalogProductFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Media\ConfigFactory
     */
    protected $catalogProductMediaConfigFactory;

    /** @var \Magento\CatalogInventory\Api\StockStateInterface */
    protected $stockState;

    /**
     * @var \Drip\Connect\Api\ProductDetailsResponseFactory
     */
    protected $responseFactory;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $configurable;

    /**
     * @var \Drip\Connect\Helper\Product
     */
    protected $connectProductHelper;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Drip\Connect\Api\ProductDetailsResponseFactory $responseFactory,
        \Drip\Connect\Helper\Product $connectProductHelper
    ) {
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->stockState = $stockState;
        $this->configurable = $configurable;
        $this->responseFactory = $responseFactory;
        $this->connectProductHelper = $connectProductHelper;
    }

    /**
     * POST for product details
     * @param string $productId
     * @return \Drip\Connect\Api\ProductDetailsResponse
     */
    public function showDetails($productId)
    {
        $response = $this->responseFactory->create();
        $product = $this->catalogProductFactory->create()->load($productId);
        $parentProduct = null;

        $productImage = $product->getImage();
        if (empty($productImage)) {
            $parentProduct = $this->getParentProduct($product);
            if ($parentProduct) {
                $productImage = $parentProduct->getImage();
            }
        }
        if (!empty($productImage)) {
            $productImage = $this->catalogProductMediaConfigFactory->create()->getMediaUrl($productImage);
        }
        $qty = $this->stockState->getStockQty($productId);

        // A child product that is not visible individually has no frontend page of
        // its own, so its url would 404. Point at the configurable parent instead.
        $urlProduct = $product;
        if ($product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE) {
            if ($parentProduct === null) {
                $parentProduct = $this->getParentProduct($product);
            }
            if ($parentProduct) {
                $urlProduct = $parentProduct;
            }
        }
        $productUrl = $this->connectProductHelper->getProductUrl($urlProduct);

        $response->setData(['product_url' => $productUrl, 'image_url' => $productImage, 'stock_quantity' => $qty]);

        return $response;
    }

    /**
     * Loads the configurable parent of a child product
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product|false parent product, or false when there is none
     */
    private function getParentProduct($product)
    {
        if ($product->getTypeId() == 'configurable') {
            return false;
        }

        $parentProductId = $this->getParentId($product->getId());
        if (!$parentProductId) {
            return false;
        }

        return $this->catalogProductFactory->create()->load($parentProductId);
    }

    /**
     * Gets parent product id
     * @param int $childId
     * @return int parent product id
     */
    private function getParentId($childId)
    {
        $parentConfigObject = $this->configurable->getParentIdsByChild($childId);
        if ($parentConfigObject) {
            return $parentConfigObject[0];
        }
        return false;
    }
}
