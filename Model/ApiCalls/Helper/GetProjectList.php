<?php
namespace Drip\Connect\Model\ApiCalls\Helper;

class GetProjectList extends \Drip\Connect\Model\ApiCalls\Helper
{

    /**
     * @var \Drip\Connect\Model\ApiCalls\BaseFactory
     */
    protected $connectApiCallsBaseFactory;

    /**
     * @var \Drip\Connect\Model\ApiCalls\Request\BaseFactory
     */
    protected $connectApiCallsRequestBaseFactory;

    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        $data = []
    ) {
        $this->connectApiCallsBaseFactory = $connectApiCallsBaseFactory;
        $this->connectApiCallsRequestBaseFactory = $connectApiCallsRequestBaseFactory;

        // TODO: This likely doesn't work. I need to pass config into this class.
        $config = $configFactory->createForCurrentStoreParam();

        $this->apiClient = $this->connectApiCallsBaseFactory->create([
            'endpoint' => self::ENDPOINT_ACCOUNTS,
            'config' => $config,
        ]);

        $this->request = $this->connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::GET);
    }
}
