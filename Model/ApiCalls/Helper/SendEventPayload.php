<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

use Drip\Connect\Model\ApiCalls\WooBaseFactory;
use Drip\Connect\Model\ApiCalls\Request\BaseFactory;
use Drip\Connect\Model\Configuration;
use Drip\Connect\Model\ApiCalls\Helper as ApiCallsHelper;
use Laminas\Http\Request;

/**
 * Send payload for events
 */
class SendEventPayload extends ApiCallsHelper
{
    /** @var Configuration */
    protected $config;

    public function __construct(
        WooBaseFactory $connectApiCallsWooBaseFactory,
        BaseFactory $connectApiCallsRequestBaseFactory,
        Configuration $config,
        array $payload
    ) {
        $this->config = $config;

        $this->apiClient = $connectApiCallsWooBaseFactory->create([
            'config' => $config,
            'url' => $this->integrationUrl(),
        ]);

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(Request::METHOD_POST)
            ->setRawData(json_encode($payload));
    }

    private function integrationUrl()
    {
        $accountId = $this->config->getAccountParam();
        $integrationParam = $this->config->getIntegrationToken();
        $endpoint = "https://external-production.woo.drip.sh";
        # $endpoint = "https://external-staging.woo.drip.sh";

        if ($this->config->getTestMode()) {
            $endpoint = "http://mock:1080";
        }

        return "{$endpoint}/{$accountId}/integrations/{$integrationParam}/events";
    }
}
