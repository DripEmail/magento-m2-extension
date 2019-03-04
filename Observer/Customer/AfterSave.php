<?php

namespace Drip\Connect\Observer\Customer;

class AfterSave extends \Drip\Connect\Observer\Base
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
     * - check if customer new
     * - store old customer data (which is used in drip) to compare with later
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->connectHelper->isModuleActive()) {
            return;
        }
        $customer = $observer->getCustomer();

        if ($this->registry->registry(self::REGISTRY_KEY_CUSTOMER_IS_NEW)) {
            $this->customerHelper->proceedAccountNew($customer);
        } else {
            if ($this->registry->registry(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT)) {
                $customer->setIsSubscribed(1);
            }
            if ($this->isCustomerChanged($customer)) {
                $this->customerHelper->proceedAccount($customer);
            }
            if ($this->isUnsubscribeCallRequired($customer)) {
                $this->customerHelper->unsubscribeCustomer($customer);
            }
        }
        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_IS_NEW);
        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
    }

    /**
     * check if we need to send additional api call to cancel all subscribtions
     * (true if status change from yes to no)
     *
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool
     */
    protected function isUnsubscribeCallRequired($customer)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
        $newData = $this->customerHelper->prepareCustomerData($customer);

        return ($newData['custom_fields']['accepts_marketing'] == 'no'
            && $oldData['custom_fields']['accepts_marketing'] != 'no');
    }

    /**
     * compare orig and new data
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    protected function isCustomerChanged($customer)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
        $newData = $this->customerHelper->prepareCustomerData($customer);

        return (serialize($oldData) != serialize($newData));
    }
}
