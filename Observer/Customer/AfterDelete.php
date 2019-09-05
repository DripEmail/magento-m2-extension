<?php

namespace Drip\Connect\Observer\Customer;

class AfterDelete extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $connectCustomerHelper;

    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $connectCustomerHelper
    ) {
        parent::__construct($connectHelper, $logger);
        $this->connectCustomerHelper = $connectCustomerHelper;
    }
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();

        $this->connectCustomerHelper->proceedAccountDelete($customer);
    }
}

