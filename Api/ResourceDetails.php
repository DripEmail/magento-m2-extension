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

    public function __construct(
        \Magento\Sales\Block\Adminhtml\Order\View\Info $orderViewInfo,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory
    ) {
        $this->orderViewInfo = $orderViewInfo;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function orderDetails($orderId) {
        $url = $this->orderViewInfo->getViewUrl($orderId);
        return ['order_url' => $url];
    }

    /**
     * {@inheritdoc}
     */
    public function productDetails($productId) {
        $product = $this->catalogProductFactory->create()->load($productId);
        $productImage = $product->getImage();
        if (!empty($productImage)) {
            $productImage = $this->catalogProductMediaConfigFactory->create()->getMediaUrl($productImage);
        }
        return ['product_url' => $product->getProductUrl(), 'image_url' => $productImage];
    }
}
