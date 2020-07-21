<?php

namespace Drip\Connect\Observer\Order;

/**
 * Order before save observer
 */
class BeforeSave extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($configFactory, $logger, $storeManager);
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
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
