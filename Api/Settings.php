<?php
namespace Drip\Connect\Api;

class Settings
{
    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    /** @var \Magento\Framework\App\ProductMetadata */
    protected $productMetadata;

    /** @var \Magento\Framework\Module\ResourceInterface */
    protected $moduleResource;

    /**
    * @var \Drip\Connect\Api\SettingsResponseFactory
    */
    protected $responseFactory;

    /**
    * @var \Drip\Connect\Api\Response
    */
    protected $response;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Drip\Connect\Api\SettingsResponseFactory $responseFactory
    ) {
        $this->configFactory = $configFactory;
        $this->productMetadata = $productMetadata;
        $this->moduleResource = $moduleResource;
        $this->responseFactory = $responseFactory;
    }

    /**
     * POST for integration settings API
     * @param string $websiteId
     * @param string $accountParam
     * @param string $integrationToken
     * @return \Drip\Connect\Api\SettingsResponse
     */
    public function updateSettings($websiteId = 0, $accountParam, $integrationToken) {
        $response = $this->responseFactory->create();
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
     * GET for integration settings API
     * @param string $websiteId]
     * @return \Drip\Connect\Api\SettingsResponse
     */
    public function showStatus($websiteId = 0) {
        $response = $this->responseFactory->create();
        $config = $this->configFactory->createFromWebsiteId($websiteId);
        $response->setData([
            'account_param' => $config->getAccountParam(),
            'integration_token' => $config->getIntegrationToken(),
            'magento_version' => $this->productMetadata->getVersion(),
            'plugin_version' => $this->moduleResource->getDbVersion('Drip_Connect')
        ]);

        return $response;
    }

    /**
     * DELETE for integration settings API
     * @param string $websiteId]
     * @return \Drip\Connect\Api\SettingsResponse
     */
    public function removeIntegration($websiteId = 0) {
        $response = $this->responseFactory->create();
        $config = $this->configFactory->createFromWebsiteId($websiteId);
        $config->setAccountParam(null);
        $config->setIntegrationToken(null);
        $response->setData([
            'account_param' => $config->getAccountParam(),
            'integration_token' => $config->getIntegrationToken(),
            'magento_version' => $this->productMetadata->getVersion(),
            'plugin_version' => $this->moduleResource->getDbVersion('Drip_Connect')
        ]);

        return $response;
    }
}
