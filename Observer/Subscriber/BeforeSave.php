<?php

namespace Drip\Connect\Observer\Subscriber;

/**
 * Subscriber after save observer
 */
class BeforeSave extends \Drip\Connect\Observer\Subscriber\Base
{
    const DEFAULT_STATUS = -3;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($configFactory, $logger, $storeManager);
        $this->registry = $registry;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $subscriber = $observer->getSubscriber();
        if ($subscriber === null) {
            // Short circuit without subscriber.
            return;
        }

        $initialStatus = DEFAULT_STATUS;

        $dbSubscriber = $this->subscriberFactory->create()->load($subscriber->getId());

        if ($dbSubscriber->getId() !== null && $dbSubscriber->getId() == $subscriber->getId()) {
            $initialStatus = $dbSubscriber->getStatus();
        }

        $this->registry->register(self::SUBSCRIBER_INITIAL_STATUS_KEY, $initialStatus, true);
    }
}
