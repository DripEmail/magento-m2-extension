<?php

namespace Drip\Connect\Observer\Customer\Admin;

abstract class Base extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerCustomerFactory;

    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger
    ) {
        parent::__construct($configFactory, $logger);
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->customerHelper = $customerHelper;
    }

    protected function isActive(\Magento\Framework\Event\Observer $observer)
    {
        // When running from the admin, we need to do some more digging to determine whether we are active.
        $customer = $observer->getCustomer();
        $storeId = $this->customerHelper->getCustomerStoreId($customer);
        return $this->configFactory->create($storeId)->getIntegrationToken() !== null;
    }
}
