<?php

namespace Drip\Connect\Observer\Customer;

class BeforeAddressSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerCustomerFactory;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory
    ) {
        parent::__construct($connectHelper);
        $this->registry = $registry;
        $this->customerHelper = $customerHelper;
        $this->customerCustomerFactory = $customerCustomerFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $address = $observer->getDataObject();

        $customer = $this->customerCustomerFactory->create()->load($address->getCustomerId());

        // if editing address is already a default shipping one
        // or if editing address is going to be set as default
        // save old values
        if (($customer->getDefaultShippingAddress() && $address->getId() == $customer->getDefaultShippingAddress()->getId())
           || ($address->getDefaultShipping())
        ) {
            $oldAddr = [];
            if ($customer->getDefaultShippingAddress()) {
               $oldAddr = $this->customerHelper->getAddressFields($customer->getDefaultShippingAddress());
            }
            $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR);
            $this->registry->register(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR, $oldAddr);
        }
    }
}
