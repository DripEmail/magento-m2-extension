<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

use Drip\Connect\Model\ApiCalls\BaseFactory;
use Drip\Connect\Model\ApiCalls\Request\BaseFactory as RequestBaseFactory;
use Drip\Connect\Model\Configuration;
use Drip\Connect\Model\ApiCalls\Helper as ApiCallsHelper;
use Laminas\Http\Request;

/**
 * Get project list helper
 *
 * @todo This class doesn't seem to be called from anywhere. Confirm that it is dead.
 */
class GetProjectList extends ApiCallsHelper
{
    protected $apiClient;
    protected $request;

    public function __construct(
        BaseFactory $connectApiCallsBaseFactory,
        RequestBaseFactory $connectApiCallsRequestBaseFactory,
        Configuration $config,
        $data = []
    ) {
        $this->apiClient = $connectApiCallsBaseFactory->create([
            'endpoint' => self::ENDPOINT_ACCOUNTS,
            'config' => $config,
        ]);

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(Request::METHOD_GET);
    }
}
