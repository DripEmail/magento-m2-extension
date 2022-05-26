<?php

namespace Drip\Connect\Api;

/**
 * Class to capture the order details api response.
 */
class OrderDetailsResponse extends \Magento\Framework\DataObject
{
    /**
     * Get order url
     *
     * @return string
     */
    public function getOrderUrl()
    {
        return $this->getData('order_url');
    }
}
