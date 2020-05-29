<?php
namespace Drip\Connect\Api;
interface SettingsInterface {
    /**
     * POST for integration settings API
     * @param string $websiteId
     * @param string $accountParam
     * @param string $integrationToken
     * @return string
     */
     public function updateSettings($websiteId = 0, $accountParam, $integrationToken);

    /**
     * GET for integration settings API
     * @param string $websiteId]
     * @return string
     */
    public function showStatus($websiteId = 0);

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
