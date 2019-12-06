<?php
namespace Drip\Connect\Cron;

use \Magento\Store\Model\ScopeInterface;
use \Drip\Connect\Model\Source\SyncState;
use \Magento\Store\Model\Store;

class Orders
{
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    protected $salesResourceModelOrderCollectionFactory;

    /** @var \Drip\Connect\Model\Transformer\OrderFactory */
    protected $orderTransformerFactory;

    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\Batches\OrdersFactory */
    protected $connectApiCallsHelperBatchesOrdersFactory;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Drip\Connect\Logger\Logger */
    protected $logger;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesResourceModelOrderCollectionFactory,
        \Drip\Connect\Model\Transformer\OrderFactory $orderTransformerFactory,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Model\ApiCalls\Helper\Batches\OrdersFactory $connectApiCallsHelperBatchesOrdersFactory,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->salesResourceModelOrderCollectionFactory = $salesResourceModelOrderCollectionFactory;
        $this->orderTransformerFactory = $orderTransformerFactory;
        $this->configFactory = $configFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->connectApiCallsHelperBatchesOrdersFactory = $connectApiCallsHelperBatchesOrdersFactory;
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
        $globalConfig = $this->configFactory->createForGlobalScope();

        ini_set('memory_limit', $globalConfig->getMemoryLimit());

        $storeIds = [];
        $stores = $this->storeManager->getStores(false, false);

        $trackDefaultStatus = false;

        if ($globalConfig->getOrdersSyncState() == SyncState::QUEUED) {
            $trackDefaultStatus = true;
            $storeIds = array_keys($stores);
            $globalConfig->setOrdersSyncState(SyncState::PROGRESS);
        } else {
            foreach ($stores as $storeId => $store) {
                $storeConfig = $this->configFactory->create($storeId);
                if ($storeConfig->getOrdersSyncState() == SyncState::QUEUED) {
                    $storeIds[] = $storeId;
                }
            }
        }

        $statuses = [];
        foreach ($storeIds as $storeId) {
            $storeConfig = $this->configFactory->create($storeId);

            if (!$storeConfig->isEnabled()) {
                continue;
            }

            // Back up the current store ID and overwrite it for context.
            $prevStoreId = $this->storeManager->getStore()->getId();
            $this->storeManager->setCurrentStore($storeId);

            try {
                $result = $this->syncOrdersForStore($storeConfig);
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

            $storeConfig->setOrdersSyncState($status);
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
            $globalConfig->setOrdersSyncState($status);
        }
    }

    /**
     * @param \Drip\Connect\Model\Configuration $config
     *
     * @return bool
     */
    protected function syncOrdersForStore(\Drip\Connect\Model\Configuration $config)
    {
        $config->setOrdersSyncState(SyncState::PROGRESS);

        $result = true;
        $page = 1;
        do {
            $collection = $this->salesResourceModelOrderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('state', ['nin' => [
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_CLOSED
                    ]])
                ->addFieldToFilter('store_id', $config->getStoreId())
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batch = [];
            foreach ($collection as $order) {
                /** @var \Drip\Connect\Model\Transformer\Order */
                $orderTransformer = $this->orderTransformerFactory->create([
                    'order' => $order,
                    'config' => $config,
                ]);
                if ($orderTransformer->isCanBeSent()) {
                    $data = $orderTransformer->getOrderDataNew();
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
                $response = $this->connectApiCallsHelperBatchesOrdersFactory->create([
                    'config' => $config,
                    'batch' => $batch,
                ])->call();

                if (empty($response) || $response->getResponseCode() != 202) { // drip success code for this action
                    $result = false;
                    break;
                }
            }

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
