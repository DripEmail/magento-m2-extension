<?php
namespace Drip\Connect\Api;
use Drip\Connect\Api\ResourceDetailsInterface;

class ResourceDetails implements ResourceDetailsInterface
{
    /** @var \Magento\Sales\Block\Adminhtml\Order\View\Info */
    protected $orderViewInfo;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $catalogProductFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Media\ConfigFactory
     */
    protected $catalogProductMediaConfigFactory;

    /**
    * @var \Drip\Connect\Api\OrderDetailsResponseFactory
    */
    protected $responseFactory1;

		/**
    * @var \Drip\Connect\Api\ProductDetailsResponseFactory
    */
    protected $responseFactory2;

    public function __construct(
        \Magento\Sales\Block\Adminhtml\Order\View\Info $orderViewInfo,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Drip\Connect\Api\OrderDetailsResponseFactory $responseFactory1,
        \Drip\Connect\Api\ProductDetailsResponseFactory $responseFactory2
    ) {
        $this->orderViewInfo = $orderViewInfo;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->responseFactory1 = $responseFactory1;
        $this->responseFactory2 = $responseFactory2;
    }

    /**
     * {@inheritdoc}
     */
    public function orderDetails($orderId) {
        $response = $this->responseFactory1->create();
        $url = $this->orderViewInfo->getViewUrl($orderId);

        $response->setData(['order_url' => $url]);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function productDetails($productId) {
        $response = $this->responseFactory2->create();
        $product = $this->catalogProductFactory->create()->load($productId);
        $productImage = $product->getImage();
        if (!empty($productImage)) {
            $productImage = $this->catalogProductMediaConfigFactory->create()->getMediaUrl($productImage);
        }

        $response->setData(['product_url' => $product->getProductUrl(), 'image_url' => $productImage]);

        return $response;
    }
}
