<?php
namespace Drip\Connect\Api;

class OrderDetailsResponse  extends \Magento\Framework\DataObject
{
    /**
     * Get order url
     *
     * @return string
     */
    public function getOrderUrl() {
        return $this->getData('order_url');
    }
}
