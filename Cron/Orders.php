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

    /** @var \Drip\Connect\Logger\Logger */
    protected $logger;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesResourceModelOrderCollectionFactory,
        \Drip\Connect\Helper\Order $orderHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Drip\Connect\Logger\Logger $logger,
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
        ini_set('memory_limit', $this->scopeConfig->getValue('dripconnect_general/api_settings/memory_limit'));

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

            // Back up the current store ID and overwrite it for context.
            $prevStoreId = $this->storeManager->getStore()->getId();
            $this->storeManager->setCurrentStore($storeId);

            try {
                $result = $this->syncOrdersForStore($storeId);
            } catch (\Exception $e) {
                $result = false;
                $this->logger->critical($e);
            } finally {
                // Restore whatever the previous store ID was.
                $this->storeManager->setCurrentStore($prevStoreId);
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
            $status_values = array_unique(array_values($statuses));
            if (count($status_values) === 0 || (
                count($status_values) === 1 &&
                $status_values[0] === SyncState::READY
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
                if ($this->orderHelper->isCanBeSent($order)) {
                    $data = $this->orderHelper->getOrderDataNew($order);
                    $data['occurred_at'] = $this->connectHelper->formatDate($order->getCreatedAt());
                    $batch[] = $data;
                } else {
                    $this->logger->warning(
                        sprintf(
                            "order with id %s can't be sent to Drip (email likely invalid)",
                            $order->getId()
                        )
                    );
                }
            }

            if (count($batch)) {
                $response = $this->orderHelper->proceedOrderBatch($batch, $storeId);

                if (empty($response) || $response->getResponseCode() != 202) { // drip success code for this action
                    $result = false;
                    break;
                }
            }

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
