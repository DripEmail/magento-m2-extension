<?php
namespace Drip\Connect\Cron;

use \Magento\Store\Model\ScopeInterface;
use \Drip\Connect\Model\Source\SyncState;
use \Magento\Store\Model\Store;

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

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesResourceModelOrderCollectionFactory,
        \Drip\Connect\Helper\Order $orderHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->salesResourceModelOrderCollectionFactory = $salesResourceModelOrderCollectionFactory;
        $this->orderHelper = $orderHelper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->connectHelper = $connectHelper;
    }

    /**
     * run orders sync for stores
     *
     * if default sync queued, get all store ids
     * else walk through stores grab storeIds queued for sync
     * loop through storeids and sync every of them with drip
     * using their own configs and sending only storerelated data
     */
    public function syncOrders()
    {
        $storeIds = [];
        $stores = $this->storeManager->getStores(false, false);

        $trackDefaultStatus = false;

        if ($this->connectHelper->getOrdersSyncStateForStore(Store::DEFAULT_STORE_ID) == SyncState::QUEUED) {
            $trackDefaultStatus = true;
            $storeIds = array_keys($stores);
            $this->connectHelper->setOrdersSyncStateToStore(
                Store::DEFAULT_STORE_ID,
                SyncState::PROGRESS
            );
        } else {
            foreach ($stores as $storeId => $store) {
                if ($this->connectHelper->getOrdersSyncStateForStore($storeId) == SyncState::QUEUED) {
                    $storeIds[] = $storeId;
                }
            }
        }

        $statuses = [];
        foreach ($storeIds as $storeId) {
            if (! $this->scopeConfig->getValue(
                'dripconnect_general/module_settings/is_enabled',
                ScopeInterface::SCOPE_STORE,
                $storeId)
            ) {
                continue;
            }

            try {
                $result = $this->syncOrdersForStore($storeId);
            } catch (\Exception $e) {
                $result = false;
                $this->logger->critical($e);
            }

            if ($result) {
                $status = SyncState::READY;
            } else {
                $status = SyncState::READYERRORS;
            }

            $statuses[$storeId] = $status;

            $this->connectHelper->setOrdersSyncStateToStore($storeId, $status);
        }

        if ($trackDefaultStatus) {
            if (count($statuses) === 0 || (
                count(array_unique($statuses)) === 1 &&
                $stauses[0] === SyncState::READY
            )) {
                $status = SyncState::READY;
            } else {
                $status = SyncState::READYERRORS;
            }
            $this->connectHelper->setOrdersSyncStateToStore(Store::DEFAULT_STORE_ID, $status);
        }
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    protected function syncOrdersForStore($storeId)
    {
        $this->connectHelper->setOrdersSyncStateToStore($storeId, SyncState::PROGRESS);

        $delay = (int) $this->scopeConfig->getValue('dripconnect_general/api_settings/batch_delay');

        $result = true;
        $page = 1;
        do {
            $collection = $this->salesResourceModelOrderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('state', array('nin' => array(
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_CLOSED
                    )))
                ->addFieldToFilter('store_id', $storeId)
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batch = [];
            foreach ($collection as $order) {
                $data = $this->orderHelper->getOrderDataNew($order);
                $data['occurred_at'] = $this->connectHelper->formatDate($order->getCreatedAt());
                $batch[] = $data;
            }

            if (count($batch)) {
                $response = $this->orderHelper->proceedOrderBatch($batch, $storeId);

                if (empty($response) || $response->getResponseCode() != 202) { // drip success code for this action
                    $result = false;
                    break;
                }

                sleep($delay);
            }

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
