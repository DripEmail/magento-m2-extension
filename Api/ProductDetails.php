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

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Drip\Connect\Api\ProductDetailsResponseFactory $responseFactory
    ) {
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->stockState = $stockState;
        $this->configurable = $configurable;
        $this->responseFactory = $responseFactory;
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
        $productImage = $product->getImage();
        if (!empty($productImage)) {
            $productImage = $this->catalogProductMediaConfigFactory->create()->getMediaUrl($productImage);
        }
        else {
            if ($product->getTypeId() != 'configurable'){
                $parentProductId = $this->getParentId($productId);
                if ($parentProductId){
                    $parentProduct = $this->catalogProductFactory->create()->load($parentProductId);
                    $productImage = $parentProduct->getImage();
                    if (!empty($productImage)) {
                        $productImage = $this->catalogProductMediaConfigFactory->create()->getMediaUrl($productImage);
                    }
                }
            }
        }
        $qty = $this->stockState->getStockQty($productId);
    
        $response->setData(['product_url' => $product->getProductUrl(), 'image_url' => $productImage, 'stock_quantity' => $qty]);

        return $response;
    }

    /**
     * Gets parent product id
     * @param int $childId
     * @return int parent product id
     */
    private function getParentId($childId)
    {
        $parentConfigObject = $this->configurable->getParentIdsByChild($childId);
	    if($parentConfigObject) {
		return $parentConfigObject[0];
	    }
	    return false;
    }
}
