<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

use Drip\Connect\Model\ApiCalls\BaseFactory;
use Drip\Connect\Model\ApiCalls\Request\BaseFactory as RequestBaseFactory;
use Drip\Connect\Model\Configuration;
use Laminas\Http\Request;

/**
 * Get the subscriber list
 *
 * @todo This class doesn't seem to be called from anywhere. Confirm that it is dead.
 */
class GetSubscriberList extends \Drip\Connect\Model\ApiCalls\Helper
{
    protected $apiClient;
    protected $request;

    public function __construct(
        BaseFactory $connectApiCallsBaseFactory,
        RequestBaseFactory $connectApiCallsRequestBaseFactory,
        Configuration $config,
        $data = []
    ) {
        $data = array_merge([
            'status' => '',
            'tags' => '',
            'subscribed_before' => '',
            'subscribed_after' => '',
            'page' => '',
            'per_page' => '',
        ], $data);

        $this->apiClient = $connectApiCallsBaseFactory->create([
            'endpoint' => $config->getAccountParam() . '/' . self::ENDPOINT_SUBSCRIBERS,
            'config' => $config,
        ]);

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(Request::METHOD_GET)
            ->setParametersGet([
                'status' => $data['status'],
                'tags' => $data['tags'],
                'subscribed_before' => $data['subscribed_before'],
                'subscribed_after' => $data['subscribed_after'],
                'page' => $data['page'],
                'per_page' => $data['per_page'],
            ]);
    }
}
