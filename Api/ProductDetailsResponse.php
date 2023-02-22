<?php

namespace Drip\Connect\Api;

/**
 * Response object for product details.
 */
class ProductDetailsResponse extends \Magento\Framework\DataObject
{
    /**
     * Get product url
     *
     * @return string
     */
    public function getProductUrl()
    {
        return $this->getData('product_url');
    }

    /**
     * Get image url
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->getData('image_url');
    }

    /**
     * Get stock quantity
     *
     * @return float
     */
    public function getStockQuantity()
    {
        return $this->getData('stock_quantity');
    }
}
