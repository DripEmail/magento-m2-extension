<?php

namespace Drip\Connect\Observer;

abstract class Base implements \Magento\Framework\Event\ObserverInterface
{
    const REGISTRY_KEY_CUSTOMER_IS_NEW = 'newcustomer';
    const REGISTRY_KEY_CUSTOMER_OLD_DATA = 'oldcustomerdata';
    const REGISTRY_KEY_CUSTOMER_OLD_ADDR = 'oldcustomeraddress';
    const REGISTRY_KEY_ORDER_OLD_DATA = 'oldorderdata';
    const REGISTRY_KEY_ORDER_ITEMS_OLD_DATA = 'oldorderitemsdata';
    const REGISTRY_KEY_SUBSCRIBER_PREV_STATE = 'oldsubscribtionstatus';
    const REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT = 'userwantstosubscribe';

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->connectHelper = $connectHelper;
        $this->registry = $registry;
    }
}
