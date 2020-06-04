<?php
namespace Drip\Connect\Api;

class ProductDetailsResponse  extends \Magento\Framework\DataObject
{
    /**
     * Get product url
     *
     * @return string
     */
    public function getProductUrl() {
        return $this->getData('product_url');
    }

    /**
     * Get image url
     *
     * @return string
     */
    public function getImageUrl() {
        return $this->getData('image_url');
    }
}
