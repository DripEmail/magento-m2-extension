<?php

namespace Drip\Connect\Observer\Subscriber;

/**
 * Subscriber after delete observer
 */
class AfterDelete extends \Drip\Connect\Observer\Subscriber\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($configFactory, $logger, $storeManager);
        $this->customerHelper = $customerHelper;
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
            \Drip\Connect\Helper\Customer::DELETED_ACTION,
            null,
            $config
        );
    }
}
