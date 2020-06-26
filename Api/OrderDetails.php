<?php
namespace Drip\Connect\Api;

/**
 * Order details REST API
 */
class OrderDetails
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
    protected $responseFactory;

    public function __construct(
        \Magento\Sales\Block\Adminhtml\Order\View\Info $orderViewInfo,
        \Drip\Connect\Api\OrderDetailsResponseFactory $responseFactory
    ) {
        $this->orderViewInfo = $orderViewInfo;
        $this->responseFactory = $responseFactory;
    }

    /**
     * GET for order details
     * @param string $orderId
     * @return \Drip\Connect\Api\OrderDetailsResponse
     */
    public function showDetails($orderId)
    {
        $response = $this->responseFactory->create();
        $url = $this->orderViewInfo->getViewUrl($orderId);

        $response->setData(['order_url' => $url]);

        return $response;
    }
}
