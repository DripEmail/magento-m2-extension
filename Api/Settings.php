<?php
namespace Drip\Connect\Api;
use Drip\Connect\Api\SettingsInterface;

class Settings implements SettingsInterface
{
		/** @var \Drip\Connect\Model\ConfigurationFactory */
		protected $configFactory;

		/** @var \Magento\Store\Model\StoreManagerInterface */
		protected $storeManager;

		public function __construct(
				\Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
		) {
				$this->configFactory = $configFactory;
        $this->storeManager = $storeManager;
		}

		/**
     * {@inheritdoc}
     */
    public function updateSettings($websiteId, $accountParam, $integrationToken) {
				$storeIds = $this->storeManager->getWebsite($websiteId)->getStoreIds();
				reset($storeIds);
				$storeId = current($storeIds);
				$config = $this->configFactory->create($storeId);
				$config->setAccountParam($accountParam);
				$config->setIntegrationToken($integrationToken);
				$config = $this->configFactory->create($storeId);
				return json_encode(['account_param' => $config->getAccountParam(), 'integration_token' => $config->getIntegrationToken()]);
    }
}
