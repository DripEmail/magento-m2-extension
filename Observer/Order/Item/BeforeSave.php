<?php

namespace Drip\Connect\Observer\Order\Item;

class BeforeSave extends \Drip\Connect\Observer\Base
{
    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Helper\Order $orderHelper,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($connectHelper, $registry);
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->connectHelper->isModuleActive()) {
            return;
        }
        $orderItem = $observer->getDataObject();

        if (!$orderItem->getId()) {
            return;
        }

        $items = $this->registry->registry(self::REGISTRY_KEY_ORDER_ITEMS_OLD_DATA);
        $items[$orderItem->getId()] = $this->orderHelper->getOrderItemStatusData($orderItem, true);

        $this->registry->unregister(self::REGISTRY_KEY_ORDER_ITEMS_OLD_DATA);
        $this->registry->register(self::REGISTRY_KEY_ORDER_ITEMS_OLD_DATA, $items);
    }
}

