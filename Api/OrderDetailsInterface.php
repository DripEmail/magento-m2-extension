<?php
namespace Drip\Connect\Api;
interface OrderDetailsInterface {
    /**
     * GET for order details
     * @param string $orderId
     * @return \Drip\Connect\Api\OrderDetailsResponse
     */
    public function showDetails($orderId);
}
