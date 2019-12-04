<?php
namespace Drip\Connect\Cron;

use \Magento\Store\Model\ScopeInterface;
use \Drip\Connect\Model\Source\SyncState;
use \Magento\Store\Model\Store;

class Customers
{
    /** @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory */
    protected $customerResourceModelCollectionFactory;

    /** @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory */
    protected $newsletterSubscriberCollectionFactory;

    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\Batches\EventsFactory */
    protected $connectApiCallsHelperBatchesEventsFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Drip\Connect\Logger\Logger */
    protected $logger;

    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerResourceModelCollectionFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $newsletterSubscriberCollectionFactory,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Drip\Connect\Model\ApiCalls\Helper\Batches\EventsFactory $connectApiCallsHelperBatchesEventsFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Model\ConfigurationFactory $configFactory
    ) {
        $this->customerResourceModelCollectionFactory = $customerResourceModelCollectionFactory;
        $this->newsletterSubscriberCollectionFactory = $newsletterSubscriberCollectionFactory;
        $this->customerHelper = $customerHelper;
        $this->connectApiCallsHelperBatchesEventsFactory = $connectApiCallsHelperBatchesEventsFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->connectHelper = $connectHelper;
        $this->configFactory = $configFactory;
    }

    /**
     * run customers sync for stores
     *
     * if default sync queued, get all store ids
     * else walk through stores grab storeIds queued for sync
     * loop through storeids and sync every of them with drip
     * using their own configs and sending only storerelated data
     */
    public function syncCustomers()
    {
        $globalConfig = $this->configFactory->createForGlobalScope();

        ini_set('memory_limit', $globalConfig->getMemoryLimit());

        $storeIds = [];
        $stores = $this->storeManager->getStores(false, false);

        $trackDefaultStatus = false;

        if ($globalConfig->getCustomersSyncState() == SyncState::QUEUED) {
            $trackDefaultStatus = true;
            $storeIds = array_keys($stores);
            $globalConfig->setCustomersSyncState(SyncState::PROGRESS);
        } else {
            foreach ($stores as $storeId => $store) {
                $storeConfig = $this->configFactory->create($storeId);
                if ($storeConfig->getCustomersSyncState() == SyncState::QUEUED) {
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
                try {
                    $customerResult = $this->syncCustomersForStore($storeConfig);
                } catch (\Exception $e) {
                    $customerResult = false;
                    $this->logger->critical($e);
                }

                try {
                    $subscriberResult = $this->syncGuestSubscribersForStore($storeConfig);
                } catch (\Exception $e) {
                    $subscriberResult = false;
                    $this->logger->critical($e);
                }

                if ($subscriberResult && $customerResult) {
                    $status = SyncState::READY;
                } else {
                    $status = SyncState::READYERRORS;
                }

                $statuses[$storeId] = $status;

                $storeConfig->setCustomersSyncState($status);
            } finally {
                // Restore whatever the previous store ID was.
                $this->storeManager->setCurrentStore($prevStoreId);
            }
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
            $globalConfig->setCustomersSyncState($status);
        }
    }

    /**
     * @param \Drip\Connect\Model\Configuration $config
     *
     * @return bool
     */
    protected function syncGuestSubscribersForStore(\Drip\Connect\Model\Configuration $config)
    {
        $config->setCustomersSyncState(SyncState::PROGRESS);

        $delay = $config->getBatchDelay();

        $result = true;
        $page = 1;
        do {
            $collection = $this->newsletterSubscriberCollectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', 0) // need only guests b/c customers have already been processed
                ->addFieldToFilter('store_id', $config->getStoreId())
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batchCustomer = [];
            $batchEvents = [];
            foreach ($collection as $subscriber) {
                $email = $subscriber->getSubscriberEmail();
                if (!$this->connectHelper->isEmailValid($email)) {
                    $this->logger->notice("Skipping newsletter subscriber event during sync due to blank email");
                    continue;
                }
                $dataCustomer = $this->customerHelper->prepareGuestSubscriberData($subscriber);
                $dataCustomer['tags'] = ['Synced from Magento'];
                $batchCustomer[] = $dataCustomer;

                $dataEvents = [
                    'email' => $email,
                    'action' => ($subscriber->getDrip()
                        ? \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_UPDATED
                        : \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_NEW),
                ];
                $batchEvents[] = $dataEvents;

                if (!$subscriber->getDrip()) {
                    $subscriber->setNeedToUpdate(1);
                    $subscriber->setDrip(1);
                }
            }

            if (count($batchCustomer)) {
                $response = $this->customerHelper->proceedAccountBatch($batchCustomer, $config->getStoreId());

                if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                    $result = false;
                    break;
                }

                $response = $this->connectApiCallsHelperBatchesEventsFactory->create([
                    'config' => $config,
                    'batch' => $batchEvents,
                ])->call();

                if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                    $result = false;
                    break;
                }

                foreach ($collection as $subscriber) {
                    if ($subscriber->getNeedToUpdate()) {
                        $subscriber->save();
                    }
                }

                sleep($delay);
            }

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }

    /**
     * @param \Drip\Connect\Model\Configuration $config
     *
     * @return bool
     */
    protected function syncCustomersForStore(\Drip\Connect\Model\Configuration $config)
    {
        $config->setCustomersSyncState(SyncState::PROGRESS);

        $delay = $config->getBatchDelay();

        $websiteId = $this->storeManager->getStore($config->getStoreId())->getWebsiteId();

        $result = true;
        $page = 1;
        do {
            $collection = $this->customerResourceModelCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('website_id', ['in' => [0, $websiteId]])
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batchCustomer = [];
            foreach ($collection as $customer) {
                $email = $customer->getData('email');
                if (!$this->connectHelper->isEmailValid($email)) {
                    $this->logger->notice("Skipping subscriber during sync due to unusable email ({$email})");
                    continue;
                }
                $dataCustomer = $this->customerHelper->prepareCustomerData($customer);
                $dataCustomer['tags'] = ['Synced from Magento'];
                $batchCustomer[] = $dataCustomer;

                if (!$customer->getDrip()) {
                    $customer->setNeedToUpdateAttribute(1);
                    $customer->setDrip(1);  // 'drip' flag on customer means it was sent to drip sometime
                }
            }

            if (count($batchCustomer)) {
                $response = $this->customerHelper->proceedAccountBatch($batchCustomer, $config->getStoreId());

                if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                    $result = false;
                    break;
                }

                foreach ($collection as $customer) {
                    if ($customer->getNeedToUpdateAttribute()) {
                        $customer->getResource()->saveAttribute($customer, 'drip');
                    }
                }

                sleep($delay);
            }

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
