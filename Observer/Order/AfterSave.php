<?php

namespace Drip\Connect\Observer\Order;

class AfterSave extends \Drip\Connect\Observer\Base
{
    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Helper\Order $orderHelper,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrderFactory $connectApiCallsHelperCreateUpdateOrderFactory,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($connectHelper, $registry);
        $this->orderHelper = $orderHelper;
        $this->connectApiCallsHelperCreateUpdateOrderFactory = $connectApiCallsHelperCreateUpdateOrderFactory;
    }

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
        $this->proceedOrder($order);
        $this->registry->unregister(self::REGISTRY_KEY_ORDER_OLD_DATA);
    }

    /**
     * drip actions on order state events
     *
     * @param \Magento\Sales\Model\Order $order
     */
    protected function proceedOrder($order)
    {
        if ($this->isSameState($order)) {
            return;
        }

        switch ($order->getState()) {
            case \Magento\Sales\Model\Order::STATE_NEW :
                // new order
                $orderData = $this->orderHelper->getOrderDataNew($order);
                $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
                    'data' => $orderData
                ])->call();
                break;
        }
    }


    /**
     * check if order state has not been changed
     */
    protected function isSameState($order)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_ORDER_OLD_DATA);
        $oldValue = $oldData['state'];
        $newValue = $order->getState();

        return ($oldValue == $newValue);
    }
}

