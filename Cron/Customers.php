<?php
namespace Drip\Connect\Cron;

use \Magento\Store\Model\ScopeInterface;
use \Drip\Connect\Model\Source\SyncState;
use \Magento\Store\Model\Store;

class Customers
{
    /** @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory */
    protected $customerResourceModelCustomerCollectionFactory;

    /** @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory */
    protected $newsletterSubscriberCollectionFactory;

    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\Batches\EventsFactory */
    protected $connectApiCallsHelperBatchesEventsFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Drip\Connect\Logger\Logger */
    protected $logger;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerResourceModelCustomerCollectionFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $newsletterSubscriberCollectionFactory,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Drip\Connect\Model\ApiCalls\Helper\Batches\EventsFactory $connectApiCallsHelperBatchesEventsFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->customerResourceModelCustomerCollectionFactory = $customerResourceModelCustomerCollectionFactory;
        $this->newsletterSubscriberCollectionFactory = $newsletterSubscriberCollectionFactory;
        $this->customerHelper = $customerHelper;
        $this->connectApiCallsHelperBatchesEventsFactory = $connectApiCallsHelperBatchesEventsFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->connectHelper = $connectHelper;
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
        ini_set('memory_limit', $this->scopeConfig->getValue('dripconnect_general/api_settings/memory_limit'));

        $storeIds = [];
        $stores = $this->storeManager->getStores(false, false);

        $trackDefaultStatus = false;

        if ($this->connectHelper->getCustomersSyncStateForStore(Store::DEFAULT_STORE_ID) == SyncState::QUEUED) {
            $trackDefaultStatus = true;
            $storeIds = array_keys($stores);
            $this->connectHelper->setCustomersSyncStateToStore(
                Store::DEFAULT_STORE_ID,
                SyncState::PROGRESS
            );
        } else {
            foreach ($stores as $storeId => $store) {
                if ($this->connectHelper->getCustomersSyncStateForStore($storeId) == SyncState::QUEUED) {
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
                $result = $this->syncCustomersForStore($storeId);
                if ($result) {
                    $result = $this->syncGuestSubscribersForStore($storeId);
                }
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

            $this->connectHelper->setCustomersSyncStateToStore($storeId, $status);
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
            $this->connectHelper->setCustomersSyncStateToStore(Store::DEFAULT_STORE_ID, $status);
        }
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    protected function syncGuestSubscribersForStore($storeId)
    {
        $this->connectHelper->setCustomersSyncStateToStore($storeId, SyncState::PROGRESS);

        $delay = (int) $this->scopeConfig->getValue('dripconnect_general/api_settings/batch_delay');

        $result = true;
        $page = 1;
        do {
            $collection = $this->newsletterSubscriberCollectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', 0) // need only guests b/c customers have already been processed
                ->addFieldToFilter('store_id', $storeId)
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batchCustomer = array();
            $batchEvents = array();
            foreach ($collection as $subscriber) {
                $email = $subscriber->getSubscriberEmail();
                if (!$this->connectHelper->isEmailValid($email)) {
                    $this->logger->notice("Skipping newsletter subscriber event during sync due to blank email");
                    continue;
                }
                $dataCustomer = $this->customerHelper->prepareGuestSubscriberData($subscriber);
                $dataCustomer['tags'] = array('Synced from Magento');
                $batchCustomer[] = $dataCustomer;

                $dataEvents = array(
                    'email' => $email,
                    'action' => ($subscriber->getDrip()
                        ? \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_UPDATED
                        : \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_NEW),
                );
                $batchEvents[] = $dataEvents;

                if (!$subscriber->getDrip()) {
                    $subscriber->setNeedToUpdate(1);
                    $subscriber->setDrip(1);
                }
            }

            if (count($batchCustomer)) {
                $response = $this->customerHelper->proceedAccountBatch($batchCustomer, $storeId);

                if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                    $result = false;
                    break;
                }

                $response = $this->connectApiCallsHelperBatchesEventsFactory->create([
                    'data' => [
                        'batch' => $batchEvents,
                        'store_id' => $storeId,
                    ]
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
     * @param int $storeId
     *
     * @return bool
     */
    protected function syncCustomersForStore($storeId)
    {
        $this->connectHelper->setCustomersSyncStateToStore($storeId, SyncState::PROGRESS);

        $delay = (int) $this->scopeConfig->getValue('dripconnect_general/api_settings/batch_delay');

        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        $result = true;
        $page = 1;
        do {
            $collection = $this->customerResourceModelCustomerCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('website_id', ['in' => [0, $websiteId]])
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batchCustomer = array();
            foreach ($collection as $customer) {
                $dataCustomer = $this->customerHelper->prepareCustomerData($customer);
                $dataCustomer['tags'] = array('Synced from Magento');
                $batchCustomer[] = $dataCustomer;

                if (!$customer->getDrip()) {
                    $customer->setNeedToUpdateAttribute(1);
                    $customer->setDrip(1);  // 'drip' flag on customer means it was sent to drip sometime
                }
            }

            if (count($batchCustomer)) {
                $response = $this->customerHelper->proceedAccountBatch($batchCustomer, $storeId);

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
