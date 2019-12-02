<?php

namespace Drip\Connect\Observer\Customer;

class Login extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $connectCustomerHelper;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $connectCustomerHelper
    ) {
        parent::__construct($configFactory, $logger);
        $this->connectCustomerHelper = $connectCustomerHelper;
    }
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();

        $this->connectCustomerHelper->proceedLogin($customer);
    }
}
