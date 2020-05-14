<?php
namespace Drip\Connect\Model\ApiCalls;

/**
 * constructor must be implemented individually for every call helper
 *
 * two classes should be instantiated in every constructor: ApiClient and Request
 */
abstract class Helper
{
    const PROVIDER_NAME = 'magento';
    const ENDPOINT_ACCOUNTS = 'accounts';
    const ENDPOINT_SUBSCRIBERS = 'subscribers';
    const ENDPOINT_EVENTS = 'events';
    const ENDPOINT_ORDERS = 'shopper_activity/order';
    const ENDPOINT_REFUNDS = 'refunds';
    const ENDPOINT_CART = 'shopper_activity/cart';
    const ENDPOINT_PRODUCT = 'shopper_activity/product';

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
        $response = $this->apiClient->callApi($this->request);
        if (!empty($response->getResponseData()['errors'])) {
            $response->_setError($response->getResponseData()['errors'][0]['message']);
        }

        return $response;
    }
}
