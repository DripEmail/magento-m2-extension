<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

use \Drip\Connect\Model\ApiCalls\BaseFactory;
use \Drip\Connect\Model\ApiCalls\Request\BaseFactory as RequestBaseFactory;
use \Drip\Connect\Model\Configuration;
use \Laminas\Http\Request;

/**
 * Create or update a subscriber.
 */
class CreateUpdateSubscriber extends \Drip\Connect\Model\ApiCalls\Helper
{
    public function __construct(
        BaseFactory $connectApiCallsBaseFactory,
        RequestBaseFactory $connectApiCallsRequestBaseFactory,
        Configuration $config,
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
            ->setMethod(Request::METHOD_POST)
            ->setRawData(json_encode($subscribersInfo));
    }
}
