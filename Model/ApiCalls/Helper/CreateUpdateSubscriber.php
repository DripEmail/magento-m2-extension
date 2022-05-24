<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

/**
 * Create or update a subscriber.
 */
class CreateUpdateSubscriber extends \Drip\Connect\Model\ApiCalls\Helper
{
    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\Configuration $config,
        $data = []
    ) {
        $this->apiClient = $connectApiCallsBaseFactory->create([
            'endpoint' => $config->getAccountParam() . '/' . self::ENDPOINT_SUBSCRIBERS,
            'config' => $config,
        ]);

        $subscribersInfo = [
            'subscribers' => [
                $data
            ]
        ];

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($subscribersInfo));
    }
}
