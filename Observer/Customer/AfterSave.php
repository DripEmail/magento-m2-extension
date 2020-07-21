<?php

namespace Drip\Connect\Observer\Customer;

/**
 * Customer after save observer
 */
class AfterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /**
     * constructor
     */
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
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        return $this->customerHelper->sendObserverCustomerEvent(
            $observer,
            $this->configFactory,
            \Drip\Connect\Helper\Customer::UPDATED_ACTION
        );
    }
}
