<?php

namespace Drip\Connect\Model;

class Configuration
{
    const ACCOUNT_PARAM_PATH = 'dripconnect_general/api_settings/account_param';
    const WIS_URL_PATH = 'dripconnect_general/api_settings/wis_url';
    const INTEGRATION_TOKEN = 'dripconnect_general/api_settings/integration_token';
    const SALT_PATH = 'dripconnect_general/module_settings/salt';
    const LOG_SETTINGS_PATH = 'dripconnect_general/log_settings';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    protected $configWriter;

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
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager,
     * @param int $websiteId The ID of the Website
     */
    public function __construct(
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        int $websiteId
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->websiteId = $websiteId;

        if ($this->websiteId == 0) {
            $this->scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        } else {
            $this->scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
        }

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
        return $this->getConfig(self::ACCOUNT_PARAM_PATH);
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
        $this->setConfig(self::ACCOUNT_PARAM_PATH, $accountParam);
    }

        /**
     * @param string $integrationToken
     */
    public function setIntegrationToken($integrationToken)
    {
        $this->setConfig(self::INTEGRATION_TOKEN, $integrationToken);
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
    protected function setConfig($path, $val)
    {
        $this->configWriter->save(
                $path,
                $val,
                $this->scope,
                $this->websiteId
        );
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
               $store->resetConfig();
         }
    }

}
