<?php

namespace Drip\Connect\Observer\Customer;

class SubscriberAfterDelete extends \Drip\Connect\Observer\Base
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
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $subscriber = $observer->getSubscriber();

        $this->connectCustomerHelper->proceedSubscriberDelete($subscriber);
    }
}

