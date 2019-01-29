<?php

namespace Drip\Connect\Observer\Customer;

class AfterDelete extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $connectCustomerHelper;

    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Helper\Customer $connectCustomerHelper
    ) {
        parent::__construct($connectHelper, $registry);
        $this->connectCustomerHelper = $connectCustomerHelper;
    }
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->connectHelper->isModuleActive()) {
            return;
        }
        $customer = $observer->getCustomer();

        $this->connectCustomerHelper->proceedAccountDelete($customer);
    }
}
