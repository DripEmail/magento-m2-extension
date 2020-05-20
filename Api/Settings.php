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
		 * Retrieves Drip account_id
		 * @param string $websiteId
		 * @return string
		 */
    public function getAccountId($websiteId) {
				$storeIds = $this->storeManager->getWebsite($websiteId)->getStoreIds();
				reset($storeIds);
				$storeId = current($storeIds);
				$config = $this->configFactory->create($storeId);
				return json_encode(['account_id' => $config->getAccountId()]);
    }
}
