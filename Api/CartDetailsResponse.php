<?php
namespace Drip\Connect\Api;

class CartDetailsResponse extends \Magento\Framework\DataObject
{
    /**
     * Get order url
     *
     * @return string
     */
    public function getCartUrl() {
        return $this->getData('cart_url');
    }
}
