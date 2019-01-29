<?php

namespace Drip\Connect\Observer\Order;

class BeforeSave extends \Drip\Connect\Observer\Base
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->connectHelper->isModuleActive()) {
            return;
        }
        $order = $observer->getEvent()->getOrder();
        if (!$order->getId()) {
            return;
        }
        $data = [
            'total_refunded' => $order->getOrigData('total_refunded'),
            'state' => $order->getOrigData('state'),
        ];
        $this->registry->unregister(self::REGISTRY_KEY_ORDER_OLD_DATA);
        $this->registry->register(self::REGISTRY_KEY_ORDER_OLD_DATA, $data);
    }
}
