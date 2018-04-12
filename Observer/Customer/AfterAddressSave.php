<?php

namespace Drip\Connect\Observer\Customer;

class AfterAddressSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerCustomerFactory;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory
    ) {
        parent::__construct($connectHelper, $registry);
        $this->customerHelper = $customerHelper;
        $this->customerCustomerFactory = $customerCustomerFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->connectHelper->isModuleActive()) {
            return;
        }

        // change was not done in address we use in drip
        if (empty($this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR))) {
            return;
        }

        $address = $observer->getDataObject();

        $customer = $this->customerCustomerFactory->create()->load($address->getCustomerId());

        if ($this->isAddressChanged($address)) {
            $this->customerHelper->proceedAccount($customer);
        }

        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR);
    }

    /**
     * compare orig and new data
     *
     * @param \Magento\Customer\Model\Address $address
     */
    protected function isAddressChanged($address)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR);
        $newData = $this->customerHelper->getAddressFields($address);

        return (serialize($oldData) != serialize($newData));
    }
}
