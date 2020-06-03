<?php
namespace Drip\Connect\Api;
interface ResourceDetailsInterface {
    /**
     * GET for order details
     * @param string $orderId
     * @return array
     */
    public function orderDetails($orderId);

    /**
     * POST for product details
     * @param string $productId
     * @return array
     */
    public function productDetails($productId);
}
