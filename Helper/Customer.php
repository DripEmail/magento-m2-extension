<?php

namespace Drip\Connect\Helper;

/**
 * Customer helpers
 */
class Customer extends \Magento\Framework\App\Helper\AbstractHelper
{
    const LOGIN_ACTION = 'login';
    const CREATED_ACTION = 'created';
    const UPDATED_ACTION = 'updated';
    const DELETED_ACTION = 'deleted';

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

    /** @var \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory */
    protected $connectApiCallsHelperSendEventPayloadFactory;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\GroupFactory $customerGroupFactory,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateSubscriberFactory $apiCallsCreateUpdateSubscriberFactory,
        \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory $connectApiCallsHelperRecordAnEventFactory,
        \Drip\Connect\Helper\Quote $quoteHelper,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory $connectApiCallsHelperSendEventPayloadFactory
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->customerGroupFactory = $customerGroupFactory;
        $this->remoteAddress = $context->getRemoteAddress();
        $this->apiCallsCreateUpdateSubscriberFactory = $apiCallsCreateUpdateSubscriberFactory;
        $this->connectApiCallsHelperRecordAnEventFactory = $connectApiCallsHelperRecordAnEventFactory;
        $this->header = $context->getHttpHeader();
        $this->quoteHelper = $quoteHelper;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->storeManager = $storeManager;
        $this->subscriberFactory = $subscriberFactory;
        $this->registry = $registry;
        $this->connectHelper = $connectHelper;
        $this->connectApiCallsHelperSendEventPayloadFactory = $connectApiCallsHelperSendEventPayloadFactory;
    }

    public function sendEvent($payload, \Drip\Connect\Model\Configuration $config)
    {
        return $this->connectApiCallsHelperSendEventPayloadFactory->create([
            'config' => $config,
            'payload' => $payload,
        ])->call();
    }

    public function sendObserverCustomerEvent(
        \Magento\Framework\Event\Observer $observer,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        $action
    ) {
        $customer = $observer->getCustomer();
        if ($customer === null) {
            return;
        }

        return $this->sendCustomerEvent($customer, $configFactory, $action);
    }

    public function sendCustomerEvent(
        $customer, // maybe \Magento\Customer\Model\Data\Customer OR Magento\Customer\Model\Customer\Interceptor
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        $action
    ) {
        $storeId = $this->getCustomerStoreId($customer);
        $payload = [
            'action' => $action,
            'store_id' => $storeId
        ];

        if ($customer->getId() !== null) {
            $payload['customer_id'] = $customer->getId();
        } elseif ($customer->getEmail() !== null) {
            $payload['email'] = $customer->getEmail();
        } else {
            return;
        }

        $config = $configFactory->create($storeId);
        return $this->sendEvent($payload, $config);
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
    public function accountActionsForGuestCheckout(
        \Magento\Sales\Model\Order $order,
        \Drip\Connect\Model\Configuration $config
    ) {
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
     * @param \Drip\Connect\Model\Configuration $config
     *
     * @return bool
     */
    public function isCustomerExists($email, \Drip\Connect\Model\Configuration $config)
    {
        return (bool) $this->getCustomerByEmail($email, $config);
    }

    /**
     * @param string $email
     * @param \Drip\Connect\Model\Configuration $config
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomerByEmail($email, \Drip\Connect\Model\Configuration $config)
    {
        $websiteId = $config->getWebsiteId();

        return $this->customerCustomerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);
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
}
