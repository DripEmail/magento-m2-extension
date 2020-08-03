<?php
namespace Drip\Connect\Api;

/**
 * Response object for Settings REST API
 */
class SettingsResponse extends \Magento\Framework\DataObject
{
    /**
     * Get account param
     *
     * @return string
     */
    public function getAccountParam()
    {
        return $this->getData('account_param');
    }

    /**
     * Get integration token
     *
     * @return string
     */
    public function getIntegrationToken()
    {
        return $this->getData('integration_token');
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->getData('plugin_version');
    }

    /**
     * Get magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->getData('magento_version');
    }
}
