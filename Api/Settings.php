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

	/** @var \Magento\Sales\Block\Adminhtml\Order\View\Info */
	protected $orderViewInfo;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Sales\Block\Adminhtml\Order\View\Info $orderViewInfo
    ) {
        $this->configFactory = $configFactory;
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
        $this->moduleResource = $moduleResource;
		$this->orderViewInfo = $orderViewInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSettings($websiteId = 0, $accountParam, $integrationToken) {
				$website = $this->storeManager->getWebsite($websiteId);
        $config = $this->configFactory->createFromWebsiteId($websiteId);
        $config->setAccountParam($accountParam);
        $config->setIntegrationToken($integrationToken);
        return json_encode(['account_param' => $config->getAccountParam(), 'integration_token' => $config->getIntegrationToken()]);
    }

    /**
     * {@inheritdoc}
     */
    public function showStatus($websiteId = 0) {
				$website = $this->storeManager->getWebsite($websiteId);
        $config = $this->configFactory->createFromWebsiteId($websiteId);
        return json_encode([
            'account_param' => $config->getAccountParam(),
            'integration_token' => $config->getIntegrationToken(),
            'magento_version' => $this->productMetadata->getVersion(),
            'plugin_version' => $this->moduleResource->getDbVersion('Drip_Connect')
        ]);
    }

	/**
     * {@inheritdoc}
     */
    public function orderDetails($orderId) {
		$url = $this->orderViewInfo->getViewUrl($orderId);
        return json_encode(['order_url' => $url]);
    }
}
