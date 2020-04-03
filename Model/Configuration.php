<?php

namespace Drip\Connect\Model;

class Configuration
{
    const ACCOUNT_ID_PATH = 'dripconnect_general/api_settings/account_id';
    const BEHAVIOR_PATH = 'dripconnect_general/api_settings/behavior';
    const API_URL_PATH = 'dripconnect_general/api_settings/url';
    const API_TIMEOUT_PATH = 'dripconnect_general/api_settings/timeout';
    const API_KEY_PATH = 'dripconnect_general/api_settings/api_key';
    const API_INTEGRATION_PARAM = 'dripconnect_general/api_settings/api_integration_param';
    const CUSTOMER_DATA_STATE_PATH = 'dripconnect_general/actions/sync_customers_data_state';
    const ORDER_DATA_STATE_PATH = 'dripconnect_general/actions/sync_orders_data_state';
    const MODULE_ENABLED_PATH = 'dripconnect_general/module_settings/is_enabled';
    const SALT_PATH = 'dripconnect_general/module_settings/salt';
    const LOG_SETTINGS_PATH = 'dripconnect_general/log_settings';
    const MEMORY_LIMIT_PATH = 'dripconnect_general/api_settings/memory_limit';
    const BATCH_DELAY_PATH = 'dripconnect_general/api_settings/batch_delay';

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

    public function getAccountId()
    {
        return $this->getStoreConfig(self::ACCOUNT_ID_PATH);
    }

    public function getBehavior()
    {
        return $this->getStoreConfig(self::BEHAVIOR_PATH);
    }

    public function getUrl()
    {
        return $this->getStoreConfig(self::API_URL_PATH);
    }

    public function getTimeout()
    {
        return $this->getStoreConfig(self::API_TIMEOUT_PATH);
    }

    /**
     * @param int $timeout The timeout in seconds.
     */
    public function setTimeout($timeout)
    {
        $this->setStoreConfig(self::API_TIMEOUT_PATH, $timeout);
    }

    public function getApiKey()
    {
        return $this->getStoreConfig(self::API_KEY_PATH);
    }

    public function getApiIntegrationParam()
    {
        return $this->getStoreConfig(self::API_INTEGRATION_PARAM);
    }

    public function getCustomersSyncState()
    {
        return $this->getStoreConfig(self::CUSTOMER_DATA_STATE_PATH);
    }

    /**
     * @param int $state
     */
    public function setCustomersSyncState($state)
    {
        $this->setStoreConfig(self::CUSTOMER_DATA_STATE_PATH, $state);
    }

    public function getOrdersSyncState()
    {
        return $this->getStoreConfig(self::ORDER_DATA_STATE_PATH);
    }

    /**
     * @param int $state
     */
    public function setOrdersSyncState($state)
    {
        $this->setStoreConfig(self::ORDER_DATA_STATE_PATH, $state);
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

    public function getMemoryLimit()
    {
        return $this->getStoreConfig(self::MEMORY_LIMIT_PATH);
    }

    /**
     * @return int
     */
    public function getBatchDelay()
    {
        return (int) $this->getStoreConfig(self::BATCH_DELAY_PATH);
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

    /**
     * @param string $path
     * @param mixed $val
     */
    protected function setStoreConfig($path, $val)
    {
        $this->resourceConfig->saveConfig(
            $path,
            $val,
            $this->scope,
            $this->storeId
        );
        $this->storeManager->getStore($this->storeId)->resetConfig();
    }
}
