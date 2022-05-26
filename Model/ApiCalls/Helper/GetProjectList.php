<?php

namespace Drip\Connect\Model\ApiCalls\Helper;

/**
 * Get project list helper
 *
 * @todo This class doesn't seem to be called from anywhere. Confirm that it is dead.
 */
class GetProjectList extends \Drip\Connect\Model\ApiCalls\Helper
{
    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\Configuration $config,
        $data = []
    ) {
        $this->apiClient = $connectApiCallsBaseFactory->create([
            'endpoint' => self::ENDPOINT_ACCOUNTS,
            'config' => $config,
        ]);

        $this->request = $connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::GET);
    }
}
