<?php

namespace Drip\Connect\Model;

class Configuration
{
    const WIS_URL_PATH = 'dripconnect_general/api_settings/wis_url';
    const INTEGRATION_TOKEN = 'dripconnect_general/api_settings/integration_token';
    const MODULE_ENABLED_PATH = 'dripconnect_general/module_settings/is_enabled';
    const SALT_PATH = 'dripconnect_general/module_settings/salt';
    const LOG_SETTINGS_PATH = 'dripconnect_general/log_settings';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config $resourceConfig
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var int The website's ID
     */
    protected $websiteId;

    /**
     * @var string The config's scope
     */
    protected $scope;

    /**
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager,
     * @param int $websiteId The ID of the Website
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        int $websiteId
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->websiteId = $websiteId;

		$this->scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
    }

    public function getWebsiteId()
    {
        return $this->websiteId;
    }

    public function getWisUrl()
    {
        return $this->getWebsiteConfig(self::WIS_URL_PATH);
    }

    public function getIntegrationToken()
    {
        return $this->getWebsiteConfig(self::INTEGRATION_TOKEN);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->getWebsiteConfig(self::MODULE_ENABLED_PATH);
    }

    public function getSalt()
    {
        return $this->getWebsiteConfig(self::SALT_PATH);
    }

    public function getLogSettings()
    {
        return $this->getWebsiteConfig(self::LOG_SETTINGS_PATH);
    }

    /**
     * @param string $path
     */
    protected function getWebsiteConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            $this->scope,
            $this->websiteId
        );
    }
}
