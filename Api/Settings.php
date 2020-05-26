<?php
namespace Drip\Connect\Api;
use Drip\Connect\Api\SettingsInterface;

class Settings implements SettingsInterface
{
	/** @var \Drip\Connect\Model\ConfigurationFactory */
	protected $configFactory;

	/** @var \Magento\Store\Model\StoreManagerInterface */
	protected $storeManager;

	/** @var \Magento\Framework\App\ProductMetadata */
	protected $productMetadata;

	/** @var \Magento\Framework\Module\ResourceInterface */
	protected $moduleResource;

	public function __construct(
		\Drip\Connect\Model\ConfigurationFactory $configFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\ProductMetadata $productMetadata,
		\Magento\Framework\Module\ResourceInterface $moduleResource
	) {
		$this->configFactory = $configFactory;
		$this->storeManager = $storeManager;
		$this->productMetadata = $productMetadata;
		$this->moduleResource = $moduleResource;
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
		return json_encode(['account_param' => $config->getAccountParam(), 'integration_token' => $config->getIntegrationToken()]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function showStatus($websiteId) {
		$storeIds = $this->storeManager->getWebsite($websiteId)->getStoreIds();
		reset($storeIds);
		$storeId = current($storeIds);
		$config = $this->configFactory->create($storeId);
		return json_encode([
			'account_param' => $config->getAccountParam(),
			'integration_token' => $config->getIntegrationToken(),
			'magento_version' => $this->productMetadata->getVersion(),
			'plugin_version' => $this->moduleResource->getDbVersion('Drip_Connect'),
			'enabled' => $config->isEnabled()
		]);
	}
}
