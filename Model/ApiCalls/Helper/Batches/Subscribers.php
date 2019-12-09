<?php

namespace Drip\Connect\Model\ApiCalls\Helper\Batches;

class Subscribers extends \Drip\Connect\Model\ApiCalls\Helper
{
    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\Configuration $config,
        array $batch
    ) {
        $this->apiClient = $connectApiCallsBaseFactory->create([
            'endpoint' => $config->getAccountId().'/'.self::ENDPOINT_BATCH_SUBSCRIBERS,
            'config' => $config,
        ]);

        $batchesInfo = [
            'batches' => [
                [
                    'subscribers' => $batch
                ]
            ]
        ];

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($batchesInfo));
    }
}
