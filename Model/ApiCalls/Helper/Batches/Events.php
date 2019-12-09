<?php

namespace Drip\Connect\Model\ApiCalls\Helper\Batches;

class Events extends \Drip\Connect\Model\ApiCalls\Helper
{
    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\Configuration $config,
        array $batch
    ) {
        $this->apiClient = $connectApiCallsBaseFactory->create([
            'config' => $config,
            'endpoint' => $config->getAccountId().'/'.self::ENDPOINT_BATCH_EVENTS,
        ]);

        $batchesInfo = [
            'batches' => [
                [
                    'events' => $batch
                ]
            ]
        ];

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($batchesInfo));
    }
}
