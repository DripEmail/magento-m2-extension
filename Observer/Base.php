<?php

namespace Drip\Connect\Observer;

abstract class Base implements \Magento\Framework\Event\ObserverInterface
{
    const REGISTRY_KEY_CUSTOMER_IS_NEW = 'newcustomer';
    const REGISTRY_KEY_CUSTOMER_OLD_ADDR = 'oldcustomeraddress';
    const REGISTRY_KEY_CUSTOMER_OLD_DATA = 'oldcustomerdata';
    const REGISTRY_KEY_NEW_GUEST_SUBSCRIBER = 'newguestsubscriber';
    const REGISTRY_KEY_NEW_USER_SUBSCRIBE_STATE = 'is_new_user_wants_to_subscribe';
    const REGISTRY_KEY_ORDER_ITEMS_OLD_DATA = 'oldorderitemsdata';
    const REGISTRY_KEY_ORDER_OLD_DATA = 'oldorderdata';
    const REGISTRY_KEY_SUBSCRIBER_PREV_STATE = 'oldsubscriptionstatus';
    const REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT = 'userwantstosubscribe';

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Drip\Connect\Logger\Logger */
    protected $logger;

    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Logger\Logger $logger
    ) {
        $this->connectHelper = $connectHelper;
        $this->logger = $logger;
    }

    abstract protected function executeWhenEnabled(\Magento\Framework\Event\Observer $observer);

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isActive()) {
            return;
        }

        $myClass = get_class($this);
        $this->logger->info("Observer triggered: {$myClass}");

        try {
            $this->executeWhenEnabled($observer);
        } catch (\Exception $e) {
            // We should never blow up a customer's site due to bugs in our code.
            $this->logger->critical($e);
        }
    }

    /**
     * Override when you have a more specific concept of active than just the
     * current scope.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     */
    protected function isActive(\Magento\Framework\Event\Observer $observer)
    {
        // TODO: Refactor connectHelper out of the class and call this directly.
        return $this->connectHelper->isModuleActive();
    }
}
