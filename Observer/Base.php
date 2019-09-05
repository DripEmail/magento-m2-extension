<?php

namespace Drip\Connect\Observer;

abstract class Base implements \Magento\Framework\Event\ObserverInterface
{
    const REGISTRY_KEY_CUSTOMER_IS_NEW = 'newcustomer';
    const REGISTRY_KEY_CUSTOMER_OLD_DATA = 'oldcustomerdata';
    const REGISTRY_KEY_CUSTOMER_OLD_ADDR = 'oldcustomeraddress';
    const REGISTRY_KEY_ORDER_OLD_DATA = 'oldorderdata';
    const REGISTRY_KEY_ORDER_ITEMS_OLD_DATA = 'oldorderitemsdata';
    const REGISTRY_KEY_SUBSCRIBER_PREV_STATE = 'oldsubscriptionstatus';
    const REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT = 'userwantstosubscribe';
    const REGISTRY_KEY_NEW_GUEST_SUBSCRIBER = 'newguestsubscriber';

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->connectHelper = $connectHelper;
    }

    abstract protected function executeWhenEnabled(\Magento\Framework\Event\Observer $observer);

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->connectHelper->isModuleActive()) {
            return;
        }

        $this->executeWhenEnabled($observer);
    }
}
