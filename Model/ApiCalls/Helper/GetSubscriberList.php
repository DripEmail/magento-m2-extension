<?php
namespace Drip\Connect\Model\ApiCalls\Helper;


class GetSubscriberList
    extends \Drip\Connect\Model\ApiCalls\Helper
{

    /**
     * @var \Drip\Connect\Model\ApiCalls\BaseFactory
     */
    protected $connectApiCallsBaseFactory;

    /**
     * @var \Drip\Connect\Model\ApiCalls\Request\BaseFactory
     */
    protected $connectApiCallsRequestBaseFactory;

    public function __construct($data,
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory
    )
    {
        $this->connectApiCallsBaseFactory = $connectApiCallsBaseFactory;
        $this->connectApiCallsRequestBaseFactory = $connectApiCallsRequestBaseFactory;
        $data = array_merge(array(
            'status' => '',
            'tags' => '',
            'subscribed_before' => '',
            'subscribed_after' => '',
            'page' => '',
            'per_page' => '',
        ), $data);

        $this->apiClient = $this->connectApiCallsBaseFactory->create();

        $this->request = $this->connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::GET)
            ->setParametersGet(array(
                'status' => $data['status'],
                'tags' => $data['tags'],
                'subscribed_before' => $data['subscribed_before'],
                'subscribed_after' => $data['subscribed_after'],
                'page' => $data['page'],
                'per_page' => $data['per_page'],
            ));
    }
}

