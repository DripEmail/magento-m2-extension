<?php
namespace Drip\Connect\Model\ApiCalls\Helper;

class CreateUpdateRefund extends \Drip\Connect\Model\ApiCalls\Helper
{
    const PROVIDER_NAME = 'magento';

    /** @var \Drip\Connect\Model\ApiCalls\BaseFactory */
    protected $connectApiCallsBaseFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Request\BaseFactory */
    protected $connectApiCallsRequestBaseFactory;

    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        $data = []
    ) {
        $this->connectApiCallsBaseFactory = $connectApiCallsBaseFactory;
        $this->connectApiCallsRequestBaseFactory = $connectApiCallsRequestBaseFactory;

        $config = $configFactory->createForCurrentScope();

        $this->apiClient = $this->connectApiCallsBaseFactory->create([
            'endpoint' => $config->getAccountId() . '/' . self::ENDPOINT_REFUNDS,
            'config' => $config,
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
