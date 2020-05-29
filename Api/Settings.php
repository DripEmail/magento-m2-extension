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

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $catalogProductFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Media\ConfigFactory
     */
    protected $catalogProductMediaConfigFactory;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Sales\Block\Adminhtml\Order\View\Info $orderViewInfo,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory
    ) {
        $this->configFactory = $configFactory;
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
        $this->moduleResource = $moduleResource;
        $this->orderViewInfo = $orderViewInfo;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
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

    /**
     * {@inheritdoc}
     */
    public function productDetails($productId) {
        $product = $this->catalogProductFactory->create()->load($productId);
        $productImage = $product->getImage();
        if (!empty($productImage)) {
            $productImage = $this->catalogProductMediaConfigFactory->create()->getMediaUrl($productImage);
        }
        return json_encode(['product_url' => $product->getProductUrl(), 'image_url' => $productImage]);
    }
}
