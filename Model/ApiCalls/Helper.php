<?php
namespace Drip\Connect\Model\ApiCalls;

/**
 * constructor must be implemented individually for every call helper
 *
 * two classes should be instantiated in every constructor: ApiClient and Request
 */
abstract class Helper
{
    const ENDPOINT_ACCOUNTS = 'accounts';
    const ENDPOINT_SUBSCRIBERS = 'subscribers';
    const ENDPOINT_EVENTS = 'events';
    const ENDPOINT_ORDERS = 'orders';
    const ENDPOINT_REFUNDS = 'refunds';
    const ENDPOINT_BATCH_SUBSCRIBERS = 'subscribers/batches';
    const ENDPOINT_BATCH_ORDERS = 'orders/batches';

    const MAX_BATCH_SIZE = 1000;

    /** @var \Drip\Connect\Model\ApiCalls\Base */
    protected $apiClient;

    /** @var \Drip\Connect\Model\ApiCalls\Request\Base */
    protected $request;

    /**
     * call api
     *
     * @return \Drip\Connect\Model\ApiCalls\Response\Base
     */
    public function call()
    {
        return $this->apiClient->callApi($this->request);
    }
}
