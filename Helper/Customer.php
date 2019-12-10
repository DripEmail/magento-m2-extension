<?php

namespace Drip\Connect\Helper;

class Customer extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Drip\Connect\Logger\Logger */
    protected $logger;

    /** @var \Magento\Customer\Model\GroupFactory */
    protected $customerGroupFactory;

    /** @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress */
    protected $remoteAddress;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateSubscriberFactory */
    protected $apiCallsCreateUpdateSubscriberFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory */
    protected $connectApiCallsHelperRecordAnEventFactory;

    /** @var \Magento\Framework\HTTP\Header */
    protected $header;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerCustomerFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\GroupFactory $customerGroupFactory,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateSubscriberFactory $apiCallsCreateUpdateSubscriberFactory,
        \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory $connectApiCallsHelperRecordAnEventFactory,
        \Drip\Connect\Model\ApiCalls\Helper\Batches\SubscribersFactory $connectApiCallsHelperBatchesSubscribersFactory,
        \Drip\Connect\Helper\Quote $quoteHelper,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->customerGroupFactory = $customerGroupFactory;
        $this->remoteAddress = $context->getRemoteAddress();
        $this->apiCallsCreateUpdateSubscriberFactory = $apiCallsCreateUpdateSubscriberFactory;
        $this->connectApiCallsHelperRecordAnEventFactory = $connectApiCallsHelperRecordAnEventFactory;
        $this->connectApiCallsHelperBatchesSubscribersFactory = $connectApiCallsHelperBatchesSubscribersFactory;
        $this->header = $context->getHttpHeader();
        $this->quoteHelper = $quoteHelper;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->storeManager = $storeManager;
        $this->subscriberFactory = $subscriberFactory;
        $this->registry = $registry;
        $this->connectHelper = $connectHelper;
    }

    /**
     * prepare array of guest subscriber data
     *
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param bool $updatableOnly leave only those fields which are used in update action
     *
     * @return array
     */
    public function prepareGuestSubscriberData($subscriber, $updatableOnly = true, $statusChanged = false)
    {
        $acceptsMarketing = $subscriber->isSubscribed();

        $data =  [
            'email' => (string) $subscriber->getSubscriberEmail(),
            'ip_address' => (string) $this->remoteAddress->getRemoteAddress(),
            'initial_status' => $acceptsMarketing ? 'active' : 'unsubscribed',
            'custom_fields' => [
                'accepts_marketing' => $acceptsMarketing ? 'yes' : 'no',
            ],
        ];

        if ($statusChanged) {
            $data['status'] = $acceptsMarketing ? 'active' : 'unsubscribed';
        }

        if ($updatableOnly) {
            unset($data['ip_address']);
        }

        return $data;
    }

    /**
     * prepare array of customer data we use to send in drip
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param bool $updatableOnly leave only those fields which are used in update action
     * @param bool $statusChanged whether the status has changed and should be synced
     * @param bool $overriddenStatus whether the status should be something other than what is on the customer's
     *                               is_subscribed field.
     */
    public function prepareCustomerData(
        $customer,
        $updatableOnly = true,
        $statusChanged = false,
        $overriddenStatus = null
    ) {
        if ($customer->getOrigData() && $customer->getData('email') != $customer->getOrigData('email')) {
            $newEmail = $customer->getData('email');
        } else {
            $newEmail = '';
        }

        if ($overriddenStatus !== null) {
            $status = $overriddenStatus;
        } else {
            $subscriber = $this->subscriberFactory->create()->loadByCustomerId($customer->getId());
            $status = $subscriber->isSubscribed();
        }

        $data =  [
            'email' => (string) $customer->getEmail(),
            'new_email' => ($newEmail ? $newEmail : ''),
            'ip_address' => (string) $this->remoteAddress->getRemoteAddress(),
            'user_agent' => (string) $this->header->getHttpUserAgent(),
            'initial_status' => $status ? 'active' : 'unsubscribed',
            'custom_fields' => [
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'birthday' => $customer->getDob(),
                'gender' => $this->getGenderText($customer->getGender()),
                'magento_source' => $this->connectHelper->getArea(),
                'magento_account_created' => $customer->getCreatedAt(),
                'magento_customer_group' => $this->customerGroupFactory->create()
                                                                       ->load($customer->getGroupId())
                                                                       ->getCustomerGroupCode(),
                'magento_store' => (int) $customer->getStoreId(),
                'accepts_marketing' => ($status ? 'yes' : 'no'),
            ],
        ];

        if ($statusChanged) {
            $data['status'] = $status ? 'active' : 'unsubscribed';
        }

        /*if ($customer->getDefaultShippingAddress()) {
            $data = array_merge_recursive($data, array(
                'custom_fields'=>$this->getAddressFields($customer->getDefaultShippingAddress())
            ));
        }*/

        if ($updatableOnly) {
            unset($data['custom_fields']['magento_account_created']);
            unset($data['ip_address']);
            unset($data['user_agent']);
        }

        return $data;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function prepareCustomerDataForGuestCheckout($order)
    {
        return  [
            'email' => $order->getCustomerEmail(),
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->header->getHttpUserAgent(),
            'initial_status' => 'unsubscribed',
            'custom_fields' => [
                'first_name' => $order->getCustomerFirstname(),
                'last_name' => $order->getCustomerLastname(),
                'birthday' => $order->getCustomerDob(),
                'gender' => $this->getGenderText($order->getCustomerGender()),
                'magento_source' => $this->connectHelper->getArea(),
                'magento_account_created' => $order->getCreatedAt(),
                'magento_customer_group' => 'Guest',
                'magento_store' => $order->getStoreId(),
                'accepts_marketing' => 'no',
            ],
        ];
    }

    /**
     * new customer for guest checkout
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Drip\Connect\Model\Configuration $config
     */
    public function accountActionsForGuestCheckout(\Magento\Sales\Model\Order $order, \Drip\Connect\Model\Configuration $config)
    {
        $customerData = $this->prepareCustomerDataForGuestCheckout($order);

        $this->apiCallsCreateUpdateSubscriberFactory->create([
            'config' => $config,
            'data' => $customerData
        ])->call();
    }

    /**
     * get address fields
     *
     * @param \Magento\Customer\Model\Address $address
     */
    public function getAddressFields($address)
    {
        return  [
            'city' => (string) $address->getCity(),
            'state' => (string) $address->getRegion(),
            'zip_code' => (string) $address->getPostcode(),
            'country' => (string) $address->getCountry(),
            'phone_number' => (string) $address->getTelephone(),
        ];
    }

    /**
     * @param int $genderCode
     *
     * @return string
     */
    public function getGenderText($genderCode)
    {
        if ($genderCode == 1) {
            $gender = 'Male';
        } elseif ($genderCode == 2) {
            $gender = 'Female';
        } else {
            $gender = '';
        }
        return $gender;
    }

    /**
     * @param string $email
     * @param int $storeId
     *
     * @return bool
     */
    public function isSubscriberExists($email, $storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $collection = $this->subscriberFactory->create()->getCollection()
            ->addFieldToFilter('subscriber_email', $email)
            ->addFieldToFilter('store_id', $storeId);

        return (bool) $collection->getSize();
    }

    /**
     * @param string $email
     * @param int $websiteId
     *
     * @return bool
     */
    public function isCustomerExists($email, $websiteId = null)
    {
        if ($websiteId == null) {
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
        }

        $customer = $this->customerCustomerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);

        return (bool) $customer->getId();
    }

    /**
     * drip actions for customer account change
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Drip\Connect\Model\Configuration $config
     * @param bool $acceptsMarketing whether the customer accepts marketing. Overrides the customer is_subscribed
     *                               record.
     * @param string $event The updated/created/deleted event.
     * @param bool $forceStatus Whether the customer has changed marketing preferences which should be synced to Drip.
     */
    public function proceedAccount(
        \Magento\Customer\Model\Customer $customer,
        \Drip\Connect\Model\Configuration $config,
        $acceptsMarketing = null,
        $event = \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_UPDATED,
        $forceStatus = false
    ) {
        $email = $customer->getEmail();
        if (!$this->connectHelper->isEmailValid($email)) {
            $this->logger->notice("Skipping customer account update due to invalid email ({$email})");
            return;
        }

        $customerData = $this->prepareCustomerData($customer, true, $forceStatus, $acceptsMarketing);

        $this->apiCallsCreateUpdateSubscriberFactory->create([
            'config' => $config,
            'data' => $customerData
        ])->call();

        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'config' => $config,
            'data' => [
                'email' => $email,
                'action' => $event,
            ]
        ])->call();
    }

    /**
     * Gets the first store when a customer is in website scope.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer Customer object
     *         (Note that we specifically don't use type hinting because
     *          interceptors are sometimes returned from observers.)
     * @return string Store ID
     */
    public function getCustomerStoreId($customer)
    {
        $storeId = $customer->getStoreId();
        if ((int)$storeId === 0) {
            $storeIds = $this->storeManager->getWebsite($customer->getWebsiteId())->getStoreIds();
            reset($storeIds);
            $storeId = current($storeIds);
        }
        return $storeId;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param \Drip\Connect\Model\Configuration $config
     * @param bool $forceStatus
     */
    public function proceedGuestSubscriberNew(\Magento\Newsletter\Model\Subscriber $subscriber, \Drip\Connect\Model\Configuration $config, $forceStatus = false)
    {
        $email = $subscriber->getSubscriberEmail();
        if (!$this->connectHelper->isEmailValid($email)) {
            $this->logger->notice("Skipping guest subscriber create due to invalid email ({$email})");
            return;
        }
        $data = $this->prepareGuestSubscriberData($subscriber, false, $forceStatus);

        $this->apiCallsCreateUpdateSubscriberFactory->create([
            'config' => $config,
            'data' => $data
        ])->call();

        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'config' => $config,
            'data' => [
                'email' => $email,
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_NEW,
            ]
        ])->call();
    }

    /**
     * drip actions for customer log in
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Drip\Connect\Model\Configuration $config
     */
    public function proceedLogin(\Magento\Customer\Model\Customer $customer, \Drip\Connect\Model\Configuration $config)
    {
        $this->quoteHelper->checkForEmptyQuote($customer);

        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'config' => $config,
            'data' => [
                'email' => $customer->getEmail(),
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_LOGIN,
            ]
        ])->call();
    }

    /**
     * drip actions for subscriber save
     *
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param \Drip\Connect\Model\Configuration $config
     */
    public function proceedSubscriberSave(\Magento\Newsletter\Model\Subscriber $subscriber, \Drip\Connect\Model\Configuration $config)
    {
        $data = $this->prepareGuestSubscriberData($subscriber);

        $this->apiCallsCreateUpdateSubscriberFactory->create([
            'config' => $config,
            'data' => $data
        ])->call();
    }

    /**
     * drip actions for subscriber delete
     *
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param \Drip\Connect\Model\Configuration $config
     */
    public function proceedSubscriberDelete(\Magento\Newsletter\Model\Subscriber $subscriber, \Drip\Connect\Model\Configuration $config)
    {
        $data = $this->prepareGuestSubscriberData($subscriber);
        $data['custom_fields']['accepts_marketing'] = 'no';
        $data['status'] = 'unsubscribed';

        $this->apiCallsCreateUpdateSubscriberFactory->create([
            'config' => $config,
            'data' => $data
        ])->call();
    }

    /**
     * drip actions for customer account delete
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Drip\Connect\Model\Configuration $config
     */
    public function proceedAccountDelete(\Magento\Customer\Model\Customer $customer, \Drip\Connect\Model\Configuration $config)
    {
        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'config' => $config,
            'data' => [
                'email' => $customer->getEmail(),
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_DELETED,
            ]
        ])->call();
    }

    /**
     * batch customer update
     *
     * @param array $batch
     * @param \Drip\Connect\Model\Configuration $config
     *
     * @return \Drip\Connect\Model\Restapi\Response\ResponseAbstract
     */
    public function proceedAccountBatch(array $batch, \Drip\Connect\Model\Configuration $config)
    {
        return $this->connectApiCallsHelperBatchesSubscribersFactory->create([
            'config' => $config,
            'batch' => $batch,
        ])->call();
    }
}
