<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

class SendEventPayload extends \Drip\Connect\Model\ApiCalls\Helper
{
    public function __construct(
        \Drip\Connect\Model\ApiCalls\WooBaseFactory $connectApiCallsWooBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\Configuration $config,
        array $payload
    ) {
        $accountId = $config->getAccountParam();
        $integrationParam = $config->getIntegrationToken();

        // TODO: Find a better way to do this
        $url = ($config->getTestMode()) ? "http://mock:1080/${accountId}/integrations/${integrationParam}/events" : "https://woo.drip.sh/${accountId}/integrations/${integrationParam}/events";

        $this->apiClient = $connectApiCallsWooBaseFactory->create([
            'config' => $config,
            'url' => $url,
        ]);

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($payload));
    }
}
