<?php
namespace Drip\Connect\Api;
interface ProductDetailsInterface {
    /**
     * POST for product details
     * @param string $productId
     * @return \Drip\Connect\Api\ProductDetailsResponse
     */
    public function showDetails($productId);
}
