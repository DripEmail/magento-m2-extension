<?php

namespace Drip\Connect\Observer\Customer\Admin;

/**
 * Customer admin save after observer
 */
class SaveAfter extends \Drip\Connect\Observer\Customer\Admin\Base
{
    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory
    ) {
        parent::__construct($customerCustomerFactory, $customerHelper, $configFactory, $logger);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        return $this->customerHelper->sendObserverCustomerEvent(
            $observer,
            $this->configFactory,
            'Customer/Admin/SaveAfter'
        );
    }
}
