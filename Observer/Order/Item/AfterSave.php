<?php

namespace Drip\Connect\Observer\Order\Item;

class AfterSave extends \Drip\Connect\Observer\Base
{
    static $counter = 0;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Helper\Order $orderHelper,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($connectHelper, $registry);
        $this->orderHelper = $orderHelper;
        $this->order = $order;
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

        self::$counter++;

        $items = $this->registry->registry(self::REGISTRY_KEY_ORDER_ITEMS_OLD_DATA);

        $order = $this->order->load($orderItem->getOrderId());
        $itemsCount = count($order->getAllItems());

        // after save last item of all order items
        if ($itemsCount == self::$counter) {
            if ($this->isCompleteSomeItems($order)) {
                $this->orderHelper->proceedOrderCompleted($order);
            }
        }
    }

    /**
     * should return true when order has an item(s) which changing its state
     * from not-completed to completed
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    protected function isCompleteSomeItems($order)
    {
        $oldItems = $this->registry->registry(self::REGISTRY_KEY_ORDER_ITEMS_OLD_DATA);

        foreach ($order->getAllItems() as $item) {

            $itemDataCurrent = $this->orderHelper->getOrderItemStatusData($item);
            $itemDataOld = $oldItems[$item->getId()];

            if ($this->isOrderItemComplete($itemDataCurrent)
                && !$this->isOrderItemComplete($itemDataOld)) {
                    // any of items moved to completed
                    // no matter all or not all of them
                    // we will calculate that later when prepare api call
                    return true;
            }
        }

        return false;
    }

    /**
     * check if order item's data looks like data of complete item
     *
     * @param array $itemData
     *
     * @return bool
     */
    protected function isOrderItemComplete($itemData)
    {
        if ((float)$itemData['qty_ordered'] > 0 &&
            (float)$itemData['qty_invoiced'] > 0 &&
            (float)$itemData['qty_shipped'] > 0 &&
            (float)$itemData['qty_backordered'] == 0 &&
            (float)$itemData['qty_canceled'] == 0 &&
            (float)$itemData['qty_refunded'] == 0
        ) {
            return true;
        }

        return false;
    }
}

