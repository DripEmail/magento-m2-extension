<?php
namespace Drip\Connect\Cron;

use \Magento\Store\Model\ScopeInterface;
use \Drip\Connect\Model\Source\SyncState;

class Orders
{
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    protected $salesResourceModelOrderCollectionFactory;

    /** @var \Drip\Connect\Helper\Order */
    protected $orderHelper;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Magento\Store\Api\StoreRepositoryInterface */
    protected $storeRepository;

    /**
     * array [
     *     account_id => [
     *         store_id,    // == 0 for default config
     *         store_id,
     *     ],
     * ]
     */
    protected $accounts = [];

    /**
     * constructor
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesResourceModelOrderCollectionFactory,
        \Drip\Connect\Helper\Order $orderHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->salesResourceModelOrderCollectionFactory = $salesResourceModelOrderCollectionFactory;
        $this->orderHelper = $orderHelper;
        $this->scopeConfig = $scopeConfig;
        $this->storeRepository = $storeRepository;
        $this->connectHelper = $connectHelper;
    }

    /**
     * get all queued account ids
     * run orders sync for them
     */
    public function syncOrders()
    {
        $this->getAccountsToSyncOrders();

        foreach ($this->accounts as $accountId => $stores) {
            if ($this->syncOrdersWithAccount($accountId)) {
                $status = \Drip\Connect\Model\Source\SyncState::READY;
            } else {
                $status = \Drip\Connect\Model\Source\SyncState::READYERRORS;
            }
            foreach ($stores as $storeId) {
                $this->connectHelper->setOrdersSyncStateToStore($storeId, $status);
            }
        }
    }

    /**
     * populate accounts array
     */
    protected function getAccountsToSyncOrders()
    {
        if ($this->connectHelper->getOrdersSyncStateForStore(0) == SyncState::QUEUED) {
            $defAccount = $this->scopeConfig->getValue(
                'dripconnect_general/api_settings/account_id',
                ScopeInterface::SCOPE_STORE,
                0);
            $this->accounts[$defAccount][] = 0;
        }

        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            $storeId = $store->getStoreId();
            if ($storeId == 0 ) {
                continue;
            }

            if ($this->connectHelper->getOrdersSyncStateForStore($storeId) == SyncState::QUEUED) {
                $account = $this->scopeConfig->getValue(
                    'dripconnect_general/api_settings/account_id',
                    ScopeInterface::SCOPE_STORE,
                    $storeId);
                $this->accounts[$account][] = $storeId;
            }
        }
    }

    /**
     * @param int $accountId
     *
     * @return bool
     */
    protected function syncOrdersWithAccount($accountId)
    {
        $stores = $this->accounts[$accountId];
        foreach ($stores as $storeId) {
            $this->connectHelper->setOrdersSyncStateToStore($storeId, SyncState::PROGRESS);
        }

        $result = true;
        $page = 1;
        do {
            $collection = $this->salesResourceModelOrderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('state', array('nin' => array(
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_CLOSED
                    )))
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batch = array();
            foreach ($collection as $order) {
                $data = $this->orderHelper->getOrderDataNew($order);
                $data['occurred_at'] = $order->getCreatedAt();
                $batch[] = $data;
            }

            $response = $this->orderHelper->proceedOrderBatch($batch, $accountId);

            if (empty($response) || $response->getResponseCode() != 202) { // drip success code for this action
                $result = false;
                break;
            }

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
