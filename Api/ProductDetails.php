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

    /**
     * @var \Drip\Connect\Api\ProductDetailsResponseFactory
     */
    protected $responseFactory;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Drip\Connect\Api\ProductDetailsResponseFactory $responseFactory
    ) {
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
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

        $response->setData(['product_url' => $product->getProductUrl(), 'image_url' => $productImage]);

        return $response;
    }
}
