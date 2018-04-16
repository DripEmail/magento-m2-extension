<?php

namespace Drip\Connect\Model\ApiCalls\Helper\Batches;

class Subscribers
    extends \Drip\Connect\Model\ApiCalls\Helper
{
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Drip\Connect\Model\ApiCalls\BaseFactory */
    protected $connectApiCallsBaseFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Request\BaseFactory */
    protected $connectApiCallsRequestBaseFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connectApiCallsBaseFactory = $connectApiCallsBaseFactory;
        $this->connectApiCallsRequestBaseFactory = $connectApiCallsRequestBaseFactory;

        if (empty($data['account'])) {
            $accountId = $this->scopeConfig->getValue('dripconnect_general/api_settings/account_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        } else {
            $accountId = (int)$data['account'];
        }
        $this->apiClient = $this->connectApiCallsBaseFactory->create([
            'options' => [
                'endpoint' => $accountId.'/'.self::ENDPOINT_BATCH_SUBSCRIBERS,
            ]
        ]);

        $subscribersInfo = [
            'subscribers' => $data['batch']
        ];
        $batchesInfo = [
            'batches' => [
                $subscribersInfo
            ]
        ];

        $this->request = $this->connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($batchesInfo));
    }
}
