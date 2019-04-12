<?php
namespace Drip\Connect\Cron;

use \Magento\Store\Model\ScopeInterface;
use \Drip\Connect\Model\Source\SyncState;

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

    /** @var \Magento\Store\Api\StoreRepositoryInterface */
    protected $storeRepository;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

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
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerResourceModelCustomerCollectionFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $newsletterSubscriberCollectionFactory,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Drip\Connect\Model\ApiCalls\Helper\Batches\EventsFactory $connectApiCallsHelperBatchesEventsFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \Psr\Log\LoggerInterface $logger,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->customerResourceModelCustomerCollectionFactory = $customerResourceModelCustomerCollectionFactory;
        $this->newsletterSubscriberCollectionFactory = $newsletterSubscriberCollectionFactory;
        $this->customerHelper = $customerHelper;
        $this->connectApiCallsHelperBatchesEventsFactory = $connectApiCallsHelperBatchesEventsFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeRepository = $storeRepository;
        $this->logger = $logger;
        $this->connectHelper = $connectHelper;
    }

    /**
     * get all queued account ids
     * run customers sync for them
     */
    public function syncCustomers()
    {
        $this->getAccountsToSyncCustomers();

        foreach ($this->accounts as $accountId => $stores) {
            try {
                $result = $this->syncCustomersWithAccount($accountId);
                if ($result) {
                    $result = $this->syncGuestSubscribersWithAccount($accountId);
                }
            } catch (\Exception $e) {
                $result = false;
                $this->logger->critical($e);
            }

            if ($result) {
                $status = \Drip\Connect\Model\Source\SyncState::READY;
            } else {
                $status = \Drip\Connect\Model\Source\SyncState::READYERRORS;
            }

            foreach ($stores as $storeId) {
                $this->connectHelper->setCustomersSyncStateToStore($storeId, $status);
            }
        }
    }

    /**
     * populate accounts array
     */
    protected function getAccountsToSyncCustomers()
    {
        if ($this->connectHelper->getCustomersSyncStateForStore(0) == SyncState::QUEUED) {
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

            if ($this->connectHelper->getCustomersSyncStateForStore($storeId) == SyncState::QUEUED) {
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
    protected function syncGuestSubscribersWithAccount($accountId)
    {
        $stores = $this->accounts[$accountId];
        foreach ($stores as $storeId) {
            $this->connectHelper->setCustomersSyncStateToStore($storeId, SyncState::PROGRESS);
        }

        $result = true;
        $page = 1;
        do {
            $collection = $this->newsletterSubscriberCollectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', 0) // need only guests b/c customers have already been processed
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batchCustomer = array();
            $batchEvents = array();
            foreach ($collection as $subscriber) {
                $dataCustomer = $this->customerHelper->prepareGuestSubscriberData($subscriber);
                $dataCustomer['tags'] = array('Synced from Magento');
                $batchCustomer[] = $dataCustomer;

                $dataEvents = array(
                    'email' => $subscriber->getSubscriberEmail(),
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

            $response = $this->customerHelper->proceedAccountBatch($batchCustomer, $accountId);

            if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                $result = false;
                break;
            }

            $response = $this->connectApiCallsHelperBatchesEventsFactory->create([
                'data' => [
                    'batch' => $batchEvents,
                    'account' => $accountId,
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
        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }

    /**
     * @param int $accountId
     *
     * @return bool
     */
    protected function syncCustomersWithAccount($accountId)
    {
        $stores = $this->accounts[$accountId];
        foreach ($stores as $storeId) {
            $this->connectHelper->setCustomersSyncStateToStore($storeId, SyncState::PROGRESS);
        }

        $delay = (int) $this->scopeConfig->getValue('dripconnect_general/api_settings/batch_delay');

        $result = true;
        $page = 1;
        do {
            $collection = $this->customerResourceModelCustomerCollectionFactory->create()
                ->addAttributeToSelect('*')
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

            $response = $this->customerHelper->proceedAccountBatch($batchCustomer, $accountId);

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

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
