<?php

namespace Drip\Connect\Observer\Customer;

/**
 * This is bloody awful. What we're doing is being called right before the
 * controller executes the action. Once the controller executes, the customer
 * record will be saved, and after that the subscriber stuff will be saved.
 * This means that we don't have access to the new values from the customer
 * save events, but we have access to the raw params here. So we duplicate some
 * core code to reverse engineer what we expect the newsletter status to be
 * upon a successful save. :facepalm:
 */
class NewsletterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
        parent::__construct($configFactory, $logger);
        $this->registry = $registry;
        $this->subscriberFactory = $subscriberFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->customerHelper = $customerHelper;
    }

    /**
     * save old customer subscription state
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $customerEmail = $this->customerSession->getCustomer()
            ->setStoreId($this->storeManager->getStore()->getId())
            ->getEmail();

        $subscriber = $this->subscriberFactory->create()->loadByEmail($customerEmail);

        $this->registry->unregister(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE);
        $this->registry->register(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE, $subscriber->isSubscribed());

        $this->registry->unregister(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT);
        $this->registry->register(
            self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT,
            $this->request->getparam('is_subscribed', false)
        );
    }
}
