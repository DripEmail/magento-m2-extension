<?php

namespace Drip\Connect\Observer\Subscriber;

/**
 * Subscriber after save observer
 */
class AfterSave extends \Drip\Connect\Observer\Subscriber\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($configFactory, $logger, $storeManager);
        $this->customerHelper = $customerHelper;
        $this->registry = $registry;
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

        $config = $this->configFactory->create($subscriber->getStoreId());

        return $this->customerHelper->sendSubscriberEvent(
            $subscriber,
            \Drip\Connect\Helper\Customer::UPDATED_ACTION,
            $this->registry->registry(self::SUBSCRIBER_INITIAL_STATUS_KEY),
            $config
        );
    }
}
