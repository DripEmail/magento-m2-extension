<?php

namespace Drip\Connect\Observer\Order;

/**
 * Order after save observer
 */
class AfterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Model\Transformer\OrderFactory */
    protected $orderTransformerFactory;

    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Model\Transformer\OrderFactory $orderTransformerFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($configFactory, $logger, $storeManager);
        $this->registry = $registry;
        $this->orderTransformerFactory = $orderTransformerFactory;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();

        // For some strange reason, Magento2 does not fire
        // the sales_order_save_commit_after for guest checkouts,
        // but it does for registered customer checkouts.
        // So, we only care about the sales_order_save_after
        // event if the customer is checking out as a guest,
        // otherwise we'll wait for the more desirable
        // sales_order_save_commit_after event.
        if ($event->getName() == "sales_order_save_after") {
            if (!$order->getCustomerIsGuest()) {
                return;
            }
        }

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

        $config = $this->configFactory->create($order->getStoreId());

        /** @var \Drip\Connect\Model\Transformer\Order */
        $orderTransformer = $this->orderTransformerFactory->create([
            'order' => $order,
            'config' => $config,
        ]);

        if (!$orderTransformer->isCanBeSent()) {
            return;
        }

        if ($this->isOrderNew($order)) {
            //if guest checkout, create subscriber record
            if ($order->getCustomerIsGuest()
                && ! $this->customerHelper->isCustomerExists($order->getCustomerEmail(), $config)
                && ! $this->customerHelper->isSubscriberExists($order->getCustomerEmail())
            ) {
                $this->customerHelper->accountActionsForGuestCheckout($order, $config);
            }
            // new order
            $orderTransformer->proceedOrderNew();

            return;
        }
        switch ($order->getState()) {
            case \Magento\Sales\Model\Order::STATE_COMPLETE:
                // full completed order get treated in order items observer
                // as well as partly completed order
                break;

            case \Magento\Sales\Model\Order::STATE_CANCELED:
                // cancel order
                $orderTransformer->proceedOrderCancel();
                break;

            case \Magento\Sales\Model\Order::STATE_CLOSED:
                // all refunds get processed in creditmemo observer
                break;

            default:
                // other states
                $orderTransformer->proceedOrderOther();
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
