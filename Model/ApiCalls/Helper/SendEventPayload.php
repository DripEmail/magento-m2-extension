<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

/**
 * Send payload for events
 */
class SendEventPayload extends \Drip\Connect\Model\ApiCalls\Helper
{
    /** @var \Drip\Connect\Model\Configuration */
    protected $config;

    public function __construct(
        \Drip\Connect\Model\ApiCalls\WooBaseFactory $connectApiCallsWooBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\Configuration $config,
        array $payload
    ) {
        $this->config = $config;

        $this->apiClient = $connectApiCallsWooBaseFactory->create([
            'config' => $config,
            'url' => $this->integrationUrl(),
        ]);

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($payload));
    }

    private function integrationUrl()
    {
        $accountId = $this->config->getAccountParam();
        $integrationParam = $this->config->getIntegrationToken();
        $endpoint = "https://external-production.woo.drip.sh";

        if ($this->config->getTestMode()) {
            $endpoint = "http://mock:1080";
        }

        return "${endpoint}/${accountId}/integrations/${integrationParam}/events";
    }
}
