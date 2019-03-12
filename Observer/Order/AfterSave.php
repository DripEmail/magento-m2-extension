<?php

namespace Drip\Connect\Observer\Order;

class AfterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Order */
    protected $orderHelper;

    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Helper\Order $orderHelper,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($connectHelper, $registry);
        $this->orderHelper = $orderHelper;
        $this->customerHelper = $customerHelper;
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

        if ($this->isOrderNew($order)) {
            //if guest checkout, create subscriber record
            if ($order->getCustomerIsGuest()
                && ! $this->customerHelper->isCustomerExists($order->getCustomerEmail())
                && ! $this->customerHelper->isSubscriberExists($order->getCustomerEmail())
            ) {
                $this->customerHelper->accountActionsForGuestCheckout($order);
            }
            // new order
            $this->orderHelper->proceedOrderNew($order);

            return;
        }
        switch ($order->getState()) {
            case \Magento\Sales\Model\Order::STATE_COMPLETE :
                // full completed order get treated in order items observer
                // as well as partly completed order
                break;

            case \Magento\Sales\Model\Order::STATE_CANCELED :
                // cancel order
                $this->orderHelper->proceedOrderCancel($order);
                break;

            case \Magento\Sales\Model\Order::STATE_CLOSED :
                // all refunds get processed in creditmemo observer
                break;

            default :
                // other states
                $this->orderHelper->proceedOrderOther($order);
        }
    }

    /**
     * check if current order is new
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    protected function isOrderNew($order)
    {
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
            return true;
        }

        $oldData = $this->registry->registry(self::REGISTRY_KEY_ORDER_OLD_DATA);
        if (empty($oldData['state'])) {
            return true;
        }

        return false;
    }

    /**
     * check if order state has not been changed
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    protected function isSameState($order)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_ORDER_OLD_DATA);
        $oldValue = $oldData['state'];
        $newValue = $order->getState();

        return ($oldValue == $newValue);
    }
}
