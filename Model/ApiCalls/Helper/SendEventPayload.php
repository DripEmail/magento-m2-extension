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
        $integrationParam = $config->getApiIntegrationParam();

        $this->apiClient = $connectApiCallsWooBaseFactory->create([
            'config' => $config,
            // TODO: Allow tests to override this path.
            // 'url' => "https://woo.drip.sh/${accountId}/integrations/${integrationParam}/events",
            'url' => "http://mock:1080/${accountId}/integrations/${integrationParam}/events"
        ]);

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($payload));
    }
}
