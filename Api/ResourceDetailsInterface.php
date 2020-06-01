<?php
namespace Drip\Connect\Api;
interface ResourceDetailsInterface {
    /**
     * GET for order details
     * @param string $orderId
     * @return string
     */
    public function orderDetails($orderId);

    /**
     * POST for product details
     * @param string $productId
     * @return string
     */
    public function productDetails($productId);
}
