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
     * @var int The store's ID
     */
    protected $storeId;

    /**
     * @var string The config's scope
     */
    protected $scope;

    /**
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager,
     * @param int $storeId The ID of the Store View (called `store` in the DB and code)
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        int $storeId
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->storeId = $storeId;

        // M2 requires a more modern approach of not using 0 as the default ID,
        // but rather relying on a scope type. This is great, but we don't need
        // that complexity. Simplify...
        if ((int) $storeId === \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            $this->scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        } else {
            $this->scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        }
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * The website ID attached to the current store view.
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->storeManager->getStore($this->storeId)->getWebsiteId();
    }

    public function getWisUrl()
    {
        return $this->getStoreConfig(self::WIS_URL_PATH);
    }

    public function getIntegrationToken()
    {
        return $this->getStoreConfig(self::INTEGRATION_TOKEN);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->getStoreConfig(self::MODULE_ENABLED_PATH);
    }

    public function getSalt()
    {
        return $this->getStoreConfig(self::SALT_PATH);
    }

    public function getLogSettings()
    {
        return $this->getStoreConfig(self::LOG_SETTINGS_PATH);
    }

    /**
     * @param string $path
     */
    protected function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            $this->scope,
            $this->storeId
        );
    }
}
