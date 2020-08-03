<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

/**
 * Create or update a quote
 */
class CreateUpdateQuote extends \Drip\Connect\Model\ApiCalls\Helper
{
    const PROVIDER_NAME = 'magento';
    const QUOTE_NEW = 'created';
    const QUOTE_CHANGED = 'updated';

    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Model\Configuration $config,
        $data = []
    ) {
        $this->apiClient = $connectApiCallsBaseFactory->create([
            'endpoint' => $config->getAccountParam() . '/' . self::ENDPOINT_CART,
            'config' => $config,
            'v3' => true,
        ]);

        if (!empty($data) && is_array($data)) {
            $data['version'] = $connectHelper->getVersion();
        }

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($data));
    }
}
