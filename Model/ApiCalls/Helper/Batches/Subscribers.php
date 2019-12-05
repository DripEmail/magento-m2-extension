<?php

namespace Drip\Connect\Model\ApiCalls\Helper\Batches;

class Subscribers extends \Drip\Connect\Model\ApiCalls\Helper
{
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

        // TODO: Inject config into this class.
        $config = $configFactory->create((int) $data['store_id']);

        $this->apiClient = $this->connectApiCallsBaseFactory->create([
            'endpoint' => $config->getAccountId().'/'.self::ENDPOINT_BATCH_SUBSCRIBERS,
            'config' => $config,
        ]);

        $batchesInfo = [
            'batches' => [
                [
                    'subscribers' => $data['batch']
                ]
            ]
        ];

        $this->request = $this->connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($batchesInfo));
    }
}
