<?php
namespace Drip\Connect\Model\ApiCalls\Helper;


class GetSubscriberList
    extends \Drip\Connect\Model\ApiCalls\Helper
{

    /** @var \Drip\Connect\Model\ApiCalls\BaseFactory */
    protected $connectApiCallsBaseFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Request\BaseFactory */
    protected $connectApiCallsRequestBaseFactory;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $data = []
    )
    {
        $this->connectApiCallsBaseFactory = $connectApiCallsBaseFactory;
        $this->connectApiCallsRequestBaseFactory = $connectApiCallsRequestBaseFactory;
        $this->scopeConfig = $scopeConfig;
        $data = array_merge(array(
            'status' => '',
            'tags' => '',
            'subscribed_before' => '',
            'subscribed_after' => '',
            'page' => '',
            'per_page' => '',
        ), $data);

        $this->apiClient = $this->connectApiCallsBaseFactory->create([
            'options' => [
                'endpoint' => $this->scopeConfig->getValue('dripconnect_general/api_settings/account_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'/'.self::ENDPOINT_SUBSCRIBERS
            ]
        ]);

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

