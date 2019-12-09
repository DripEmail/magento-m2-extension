<?php

namespace Drip\Connect\Observer\Order;

class AfterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Order */
    protected $orderHelper;

    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Helper\Order $orderHelper,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($configFactory, $logger);
        $this->registry = $registry;
        $this->orderHelper = $orderHelper;
        $this->customerHelper = $customerHelper;
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
        $this->proceedOrder($order);
        $this->registry->unregister(self::REGISTRY_KEY_ORDER_OLD_DATA);
    }

    /**
     * drip actions on order state events
     *
     * @param \Magento\Sales\Model\Order $order
     */
    protected function proceedOrder(\Magento\Sales\Model\Order $order)
    {
        if ($this->isSameState($order)) {
            return;
        }

        if (! $this->orderHelper->isCanBeSent($order)) {
            return;
        }

        $config = $this->configFactory->create($order->getStoreId());

        if ($this->isOrderNew($order)) {
            //if guest checkout, create subscriber record
            if ($order->getCustomerIsGuest()
                && ! $this->customerHelper->isCustomerExists($order->getCustomerEmail())
                && ! $this->customerHelper->isSubscriberExists($order->getCustomerEmail())
            ) {
                $this->customerHelper->accountActionsForGuestCheckout($order, $config);
            }
            // new order
            $this->orderHelper->proceedOrderNew($order, $config);

            return;
        }
        switch ($order->getState()) {
            case \Magento\Sales\Model\Order::STATE_COMPLETE:
                // full completed order get treated in order items observer
                // as well as partly completed order
                break;

            case \Magento\Sales\Model\Order::STATE_CANCELED:
                // cancel order
                $this->orderHelper->proceedOrderCancel($order, $config);
                break;

            case \Magento\Sales\Model\Order::STATE_CLOSED:
                // all refunds get processed in creditmemo observer
                break;

            default:
                // other states
                $this->orderHelper->proceedOrderOther($order, $config);
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
