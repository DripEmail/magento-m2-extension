<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

class CreateUpdateProduct extends \Drip\Connect\Model\ApiCalls\Helper
{
    const PRODUCT_NEW = 'created';
    const PRODUCT_CHANGED = 'updated';
    const PRODUCT_DELETED = 'deleted';

    /** @var \Drip\Connect\Model\ApiCalls\BaseFactory */
    protected $connectApiCallsBaseFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Request\BaseFactory */
    protected $connectApiCallsRequestBaseFactory;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        $data = []
    ) {
        $this->connectApiCallsBaseFactory = $connectApiCallsBaseFactory;
        $this->connectApiCallsRequestBaseFactory = $connectApiCallsRequestBaseFactory;
        $this->connectHelper = $connectHelper;

        // TODO: Inject config into this class.
        $config = $configFactory->createForCurrentScope();

        $this->apiClient = $this->connectApiCallsBaseFactory->create([
            'endpoint' => $config->getAccountId() . '/' . self::ENDPOINT_PRODUCT,
            'config' => $config,
            'v3' => true,
        ]);

        if (!empty($data) && is_array($data)) {
            $data['version'] = $this->connectHelper->getVersion();
        }

        $this->request = $this->connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($data));
    }
}
