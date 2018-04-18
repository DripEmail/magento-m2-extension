<?php
namespace Drip\Connect\Model\ApiCalls\Helper;


class CreateUpdateRefund
    extends \Drip\Connect\Model\ApiCalls\Helper
{
    const PROVIDER_NAME = 'magento';

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
    ) {
        $this->connectApiCallsBaseFactory = $connectApiCallsBaseFactory;
        $this->connectApiCallsRequestBaseFactory = $connectApiCallsRequestBaseFactory;
        $this->scopeConfig = $scopeConfig;

        $this->apiClient = $this->connectApiCallsBaseFactory->create([
            'options' => [
                'endpoint' => $this->scopeConfig->getValue('dripconnect_general/api_settings/account_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'/'.self::ENDPOINT_REFUNDS
            ]
        ]);

        $ordersInfo = [
            'refunds' => [
                $data
            ]
        ];

        $this->request = $this->connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($ordersInfo));
    }
}
