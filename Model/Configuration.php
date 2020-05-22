<?php

namespace Drip\Connect\Model;

class Configuration
{
		const ACCOUNT_PARAM_PATH = 'dripconnect_general/api_settings/account_param';
    const WIS_URL_PATH = 'dripconnect_general/api_settings/wis_url';
    const INTEGRATION_TOKEN = 'dripconnect_general/api_settings/integration_token';
    const MODULE_ENABLED_PATH = 'dripconnect_general/module_settings/is_enabled';
    const SALT_PATH = 'dripconnect_general/module_settings/salt';
    const LOG_SETTINGS_PATH = 'dripconnect_general/log_settings';
		const API_TIMEOUT_PATH = 'dripconnect_general/api_settings/timeout';

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
        return $this->getConfig(self::WIS_URL_PATH);
    }

    public function getIntegrationToken()
    {
        return $this->getConfig(self::INTEGRATION_TOKEN);
    }

		public function getAccountParam()
    {
        return $this->getStoreConfig(self::ACCOUNT_PARAM_PATH);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->getConfig(self::MODULE_ENABLED_PATH);
    }

    public function getSalt()
    {
        return $this->getConfig(self::SALT_PATH);
    }

    public function getLogSettings()
    {
        return $this->getConfig(self::LOG_SETTINGS_PATH);
    }

		/**
     * @param string $accountParam
     */
    public function setAccountParam($accountParam)
    {
        $this->setWebsiteConfig(self::ACCOUNT_PARAM_PATH, $accountParam);
    }

		/**
     * @param string $integrationToken
     */
    public function setIntegrationToken($integrationToken)
    {
        $this->setWebsiteConfig(self::INTEGRATION_TOKEN, $integrationToken);
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

    /**
     * @param string $path
     */
    protected function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            $this->scope,
            $this->websiteId
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

		/**
		 * @param string $path
		 * @param mixed $val
		 */
		protected function setWebsiteConfig($path, $val)
		{
				$this->resourceConfig->saveConfig(
						$path,
						$val,
						\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
						$this->storeId
				);
				$this->storeManager->getStore($this->storeId)->resetConfig();
		}
}
