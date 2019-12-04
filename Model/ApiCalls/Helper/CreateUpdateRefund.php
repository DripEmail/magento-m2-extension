<?php
namespace Drip\Connect\Model\ApiCalls\Helper;

// TODO: It looks like this class is only used for its provider name. If true,
//       migrate that and nuke this class.

class CreateUpdateRefund extends \Drip\Connect\Model\ApiCalls\Helper
{
    const PROVIDER_NAME = 'magento';

    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\Configuration $config,
        $data = []
    ) {
        $this->apiClient = $connectApiCallsBaseFactory->create([
            'endpoint' => $config->getAccountId() . '/' . self::ENDPOINT_REFUNDS,
            'config' => $config,
        ]);

        $ordersInfo = [
            'refunds' => [
                $data
            ]
        ];

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($ordersInfo));
    }
}
