<?php

namespace Drip\Connect\Observer\Subscriber;

/**
 * Base to handle isActive derived from subscriber store.
 */
abstract class Base extends \Drip\Connect\Observer\Base
{
    const SUBSCRIBER_INITIAL_STATUS_KEY = 'drip_subscriber_initial_status';

    /**
     * Check for activity based on subscriber store id.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     */
    protected function isActive(\Magento\Framework\Event\Observer $observer)
    {
        $subscriber = $observer->getSubscriber();
        if ($subscriber === null) {
            // Short circuit without subscriber.
            return false;
        }

        $config = $this->configFactory->create($subscriber->getStoreId());

        return $config->isActive();
    }
}
