<?php
namespace Drip\Connect\Api;

class Response  extends \Magento\Framework\DataObject
{
    /**
     * Get order url
     *
     * @return string
     */
    public function getOrderUrl() {
        return $this->getData('order_url');
    }

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

    /**
     * Get account param
     *
     * @return string
     */
    public function getAccountParam() {
        return $this->getData('account_param');
    }

    /**
     * Get integration token
     *
     * @return string
     */
    public function getIntegrationToken() {
        return $this->getData('integration_token');
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion() {
        return $this->getData('plugin_version');
    }

    /**
     * Get magento version
     *
     * @return string
     */
    public function getMagentoVersion() {
        return $this->getData('magento_version');
    }
}
