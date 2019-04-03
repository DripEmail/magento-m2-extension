<?php
namespace Drip\Connect\Model\ApiCalls\Helper;


class CreateUpdateOrder
    extends \Drip\Connect\Model\ApiCalls\Helper
{
    const PROVIDER_NAME = 'magento';

    const ACTION_NEW = 'placed';
    const ACTION_CHANGE = 'updated';
    const ACTION_PAID = 'paid';
    const ACTION_FULFILL = 'fulfilled';
    const ACTION_REFUND = 'refunded';
    const ACTION_CANCEL = 'canceled';

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
                'endpoint' => $this->scopeConfig->getValue('dripconnect_general/api_settings/account_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'/'.self::ENDPOINT_ORDERS,
                'v3' => true,
            ]
        ]);

        $this->request = $this->connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($data));
    }
}
