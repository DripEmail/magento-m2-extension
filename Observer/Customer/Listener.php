<?php

namespace Drip\Connect\Observer\Customer;

/**
 * Generic customer ORM event handler
 */
class Listener extends \Drip\Connect\Observer\Base
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
        $customer = $observer->getCustomer();
        if ($customer === null) {
            // Short circuit without customer.
            return;
        }

        $config = $this->configFactory->createFromWebsiteId($customer->getWebsiteId());

        return $this->customerHelper->sendCustomerEvent(
            $customer,
            $config,
            $observer->getEvent()->getName()
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
