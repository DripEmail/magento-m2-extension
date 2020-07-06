<?php
namespace Drip\Connect\Model\ApiCalls\Helper;

/**
 * Get the subscriber list
 *
 * @todo This class doesn't seem to be called from anywhere. Confirm that it is dead.
 */
class GetSubscriberList extends \Drip\Connect\Model\ApiCalls\Helper
{
    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\Configuration $config,
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
            ->setMethod(\Zend_Http_Client::GET)
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
