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
    protected $connectApiCallsHelperCreateUpdateSubscriberFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory */
    protected $connectApiCallsHelperRecordAnEventFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\UnsubscribeSubscriberFactory */
    protected $connectApiCallsHelperUnsubscribeSubscriberFactory;

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
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateSubscriberFactory $connectApiCallsHelperCreateUpdateSubscriberFactory,
        \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory $connectApiCallsHelperRecordAnEventFactory,
        \Drip\Connect\Model\ApiCalls\Helper\Batches\SubscribersFactory $connectApiCallsHelperBatchesSubscribersFactory,
        \Drip\Connect\Model\ApiCalls\Helper\UnsubscribeSubscriberFactory $connectApiCallsHelperUnsubscribeSubscriberFactory,
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
        $this->connectApiCallsHelperCreateUpdateSubscriberFactory = $connectApiCallsHelperCreateUpdateSubscriberFactory;
        $this->connectApiCallsHelperRecordAnEventFactory = $connectApiCallsHelperRecordAnEventFactory;
        $this->connectApiCallsHelperBatchesSubscribersFactory = $connectApiCallsHelperBatchesSubscribersFactory;
        $this->connectApiCallsHelperUnsubscribeSubscriberFactory = $connectApiCallsHelperUnsubscribeSubscriberFactory;
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
    public function prepareGuestSubscriberData($subscriber, $updatableOnly = true)
    {
        if ($subscriber->getSubscriberStatus() == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
            $acceptsMarketing = 'yes';
        } else {
            $acceptsMarketing = 'no';
        }

        $data = array (
            'email' => (string) $subscriber->getSubscriberEmail(),
            'ip_address' => (string) $this->remoteAddress->getRemoteAddress(),
            'custom_fields' => array(
                'accepts_marketing' => $acceptsMarketing,
            ),
        );

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
     */
    public function prepareCustomerData($customer, $updatableOnly = true)
    {
        if ($customer->getOrigData() && $customer->getData('email') != $customer->getOrigData('email')) {
            $newEmail = $customer->getData('email');
        } else {
            $newEmail = '';
        }
        $data = array (
            'email' => (string) $customer->getEmail(),
            'new_email' => ($newEmail ? $newEmail : ''),
            'ip_address' => (string) $this->remoteAddress->getRemoteAddress(),
            'user_agent' => (string) $this->header->getHttpUserAgent(),
            'custom_fields' => array(
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'birthday' => $customer->getDob(),
                'gender' => $this->getGenderText($customer->getGender()),
                'magento_source' => $this->connectHelper->getArea(),
                'magento_account_created' => $customer->getCreatedAt(),
                'magento_customer_group' => $this->customerGroupFactory->create()->load($customer->getGroupId())->getCustomerGroupCode(),
                'magento_store' => (int) $customer->getStoreId(),
                'accepts_marketing' => ($customer->getIsSubscribed() ? 'yes' : 'no'),
            ),
        );

        /*if ($customer->getDefaultShippingAddress()) {
            $data = array_merge_recursive($data, array('custom_fields'=>$this->getAddressFields($customer->getDefaultShippingAddress())));
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
        return array (
            'email' => $order->getCustomerEmail(),
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->header->getHttpUserAgent(),
            'custom_fields' => array(
                'first_name' => $order->getCustomerFirstname(),
                'last_name' => $order->getCustomerLastname(),
                'birthday' => $order->getCustomerDob(),
                'gender' => $this->getGenderText($order->getCustomerGender()),
                'magento_source' => $this->connectHelper->getArea(),
                'magento_account_created' => $order->getCreatedAt(),
                'magento_customer_group' => 'Guest',
                'magento_store' => $order->getStoreId(),
                'accepts_marketing' => 'no',
            ),
        );
    }

    /**
     * new customer for guest checkout
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function accountActionsForGuestCheckout($order)
    {
        $customerData = $this->prepareCustomerDataForGuestCheckout($order);

        $this->connectApiCallsHelperCreateUpdateSubscriberFactory->create([
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
        return array (
            'city' => (string) $address->getCity(),
            'state' => (string) $address->getRegion(),
            'zip_code' => (string) $address->getPostcode(),
            'country' => (string) $address->getCountry(),
            'phone_number' => (string) $address->getTelephone(),
        );
    }

    /**
     * @param int $genderCode
     *
     * @return string
     */
    public function getGenderText($genderCode) {
        if ($genderCode == 1) {
            $gender = 'Male';
        } else if ($genderCode == 2) {
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
     * drip actions for customer account create
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function proceedAccountNew($customer)
    {
        $email = $customer->getEmail();
        if (!$this->connectHelper->isEmailValid($email)) {
            $this->logger->notice("Skipping customer account create due to invalid email ({$email})");
            return;
        }

        $customerData = $this->prepareCustomerData($customer, false);
        $customerData['custom_fields']['accepts_marketing'] = $this->registry->registry(
            \Drip\Connect\Observer\Customer\CreateAccount::REGISTRY_KEY_NEW_USER_SUBSCRIBE_STATE
        );

        $this->connectApiCallsHelperCreateUpdateSubscriberFactory->create([
            'data' => $customerData
        ])->call();

        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'data' => [
                'email' => $email,
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_NEW,
            ]
        ])->call();
    }

    /**
     * drip actions for customer account change
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function proceedAccount($customer)
    {
        $customerData = $this->prepareCustomerData($customer);

        $this->connectApiCallsHelperCreateUpdateSubscriberFactory->create([
            'data' => $customerData
        ])->call();

        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'data' => [
                'email' => $customer->getEmail(),
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_UPDATED,
            ]
        ])->call();
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     */
    public function proceedGuestSubscriberNew($subscriber)
    {
        $email = $subscriber->getSubscriberEmail();
        if (!$this->connectHelper->isEmailValid($email)) {
            $this->logger->notice("Skipping guest subscriber create due to invalid email ({$email})");
            return;
        }
        $data = $this->prepareGuestSubscriberData($subscriber, false);

        $this->connectApiCallsHelperCreateUpdateSubscriberFactory->create([
            'data' => $data
        ])->call();

        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'data' => [
                'email' => $email,
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_NEW,
            ]
        ])->call();
    }

    /**
     * drip unsubscribe action
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function unsubscribe($email)
    {
        $this->connectApiCallsHelperUnsubscribeSubscriberFactory->create([
            'data' => [
                'email' => $email,
            ]
        ])->call();
    }



    /**
     * drip actions for customer log in
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function proceedLogin($customer)
    {
        $this->quoteHelper->checkForEmptyQuote($customer);
        $this->connectApiCallsHelperRecordAnEventFactory->create([
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
     */
    public function proceedSubscriberSave($subscriber)
    {
        $data = $this->prepareGuestSubscriberData($subscriber);

        $this->connectApiCallsHelperCreateUpdateSubscriberFactory->create([
            'data' => $data
        ])->call();

        if ($subscriber->getSubscriberStatus() != \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
            $this->unsubscribe($subscriber->getEmail());
        }
    }

    /**
     * drip actions for subscriber delete
     *
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     */
    public function proceedSubscriberDelete($subscriber)
    {
        $data = $this->prepareGuestSubscriberData($subscriber);
        $data['custom_fields']['accepts_marketing'] = 'no';

        $this->connectApiCallsHelperCreateUpdateSubscriberFactory->create([
            'data' => $data
        ])->call();

        $this->unsubscribe($subscriber->getEmail());
    }

    /**
     * drip actions for customer account delete
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function proceedAccountDelete($customer)
    {
        $this->connectApiCallsHelperRecordAnEventFactory->create([
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
     * @param int $storeId
     *
     * @return \Drip\Connect\Model\Restapi\Response\ResponseAbstract
     */
    public function proceedAccountBatch($batch, $storeId)
    {
        return $this->connectApiCallsHelperBatchesSubscribersFactory->create([
            'data' => [
                'batch' => $batch,
                'store_id' => $storeId,
            ]
        ])->call();
    }
}
