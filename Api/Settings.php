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

    /**
    * @var \Drip\Connect\Api\ResponseFactory
    */
    protected $responseFactory;

    /**
    * @var \Drip\Connect\Api\Response
    */
    protected $response;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Drip\Connect\Api\ResponseFactory $responseFactory
    ) {
        $this->configFactory = $configFactory;
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
        $this->moduleResource = $moduleResource;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSettings($websiteId = 0, $accountParam, $integrationToken) {
        $response = $this->responseFactory->create();
        $website = $this->storeManager->getWebsite($websiteId);
        $config = $this->configFactory->createFromWebsiteId($websiteId);
        $config->setAccountParam($accountParam);
        $config->setIntegrationToken($integrationToken);
        $response->setData([
            'account_param' => $config->getAccountParam(),
            'integration_token' => $config->getIntegrationToken(),
            'magento_version' => $this->productMetadata->getVersion(),
            'plugin_version' => $this->moduleResource->getDbVersion('Drip_Connect')
        ]);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function showStatus($websiteId = 0) {
        $response = $this->responseFactory->create();
        $website = $this->storeManager->getWebsite($websiteId);
        $config = $this->configFactory->createFromWebsiteId($websiteId);
        $response->setData([
            'account_param' => $config->getAccountParam(),
            'integration_token' => $config->getIntegrationToken(),
            'magento_version' => $this->productMetadata->getVersion(),
            'plugin_version' => $this->moduleResource->getDbVersion('Drip_Connect')
        ]);

        return $response;
    }
}
