<?php

namespace Drip\Connect\Observer\Customer\Admin;

class SaveAfter extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerCustomerFactory;

    /** @var \Magento\Framework\Session\SessionManagerInterface */
    protected $coreSession;

    /** @var \Magento\Framework\Serialize\Serializer\Json */
    protected $json;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory
    ) {
        parent::__construct($configFactory, $logger);
        $this->registry = $registry;
        $this->customerHelper = $customerHelper;
        $this->subscriberFactory = $subscriberFactory;
        $this->coreSession = $coreSession;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->json = $json;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $customerData = $observer->getCustomer();
        $customer = $this->customerCustomerFactory->create()->load($customerData->getId());

        if ($this->coreSession->getCustomerIsNew()) {
            $this->coreSession->unsCustomerIsNew();
            $acceptsMarketing = $this->registry->registry(self::REGISTRY_KEY_NEW_USER_SUBSCRIBE_STATE);
            // We force the subscriber to have a subscribed status when the
            // subscriber is new and their status is already subscribed. This
            // implies that they accepted marketing recently, so we should
            // subscribe them in Drip if they aren't already.
            $this->customerHelper->proceedAccount(
                $customer,
                $acceptsMarketing,
                \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_NEW,
                $acceptsMarketing
            );
        } elseif ($this->isCustomerChanged($customer)) {
            // We change the Drip subscriber status if the status has changed.
            // Presumably, this would happen because the subscriber requested
            // that their status change.
            $this->customerHelper->proceedAccount(
                $customer,
                null,
                \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_UPDATED,
                $this->isCustomerStatusChanged($customer)
            );
        }

        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_IS_NEW);
        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
    }

    /**
     * compare orig and new data
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    protected function isCustomerChanged($customer)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
        $newData = $this->customerHelper->prepareCustomerData($customer);

        return ($this->json->serialize($oldData) != $this->json->serialize($newData));
    }

    /**
     * Determine whether the status has changed between the old and new data
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    protected function isCustomerStatusChanged($customer)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
        // TODO: Refactor away stringly typed boolean.
        $oldStatus = $oldData['custom_fields']['accepts_marketing'] == 'yes';
        $subscriber = $this->subscriberFactory->create()->loadByEmail($customer->getEmail());
        $newStatus = $subscriber->isSubscribed();
        return $oldStatus !== $newStatus;
    }
}
