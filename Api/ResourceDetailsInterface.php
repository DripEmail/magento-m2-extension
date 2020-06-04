<?php
namespace Drip\Connect\Api;
interface ResourceDetailsInterface {
    /**
     * GET for order details
     * @param string $orderId
     * @return \Drip\Connect\Api\Response
     */
    public function orderDetails($orderId);

    /**
     * POST for product details
     * @param string $productId
     * @return \Drip\Connect\Api\Response
     */
    public function productDetails($productId);
}
