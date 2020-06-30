<?php
namespace Drip\Connect\Api;

/**
 * Response object for cart details API endpoint.
 */
class CartDetailsResponse extends \Magento\Framework\DataObject
{
    /**
     * Get cart url
     *
     * @return string
     */
    public function getCartUrl()
    {
        return $this->getData('cart_url');
    }
}
