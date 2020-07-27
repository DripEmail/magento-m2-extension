<?php

namespace Drip\Connect\Observer\Customer;

/**
 * Generic customer ORM event handler
 */
class Listener extends \Drip\Connect\Observer\Base
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();
        if ($customer === null) {
            // Short circuit without customer.
            return;
        }

        $config = $this->configFactory->createFromWebsiteId($customer->getWebsiteId());

        return $this->customerHelper->sendCustomerEvent(
            $customer,
            $config,
            $observer->getEventName()
        );
    }

    /**
     * Check for activity based on customer website id.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     */
    protected function isActive(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();
        if ($customer === null) {
            // Short circuit without customer.
            return false;
        }

        $config = $this->configFactory->createFromWebsiteId($customer->getWebsiteId());

        return $config->isActive();
    }
}
