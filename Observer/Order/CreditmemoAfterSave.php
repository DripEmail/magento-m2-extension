<?php

namespace Drip\Connect\Observer\Order;

class CreditmemoAfterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Helper\Order $orderHelper,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($connectHelper);
        $this->registry = $registry;
        $this->orderHelper = $orderHelper;
        $this->order = $order;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $this->order->load($creditMemo->getOrderId());

        $this->orderHelper->proceedOrderRefund($order, $this->refundDiff($order));


    }

    /**
     *get refund value in cents
     *
     * @param  \Magento\Sales\Model\Order $order
     *
     * @return int Refund value in cents
     */
    protected function refundDiff($order)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_ORDER_OLD_DATA);
        $oldValue = $this->connectHelper->priceAsCents($oldData['total_refunded']);
        $newValue = $this->connectHelper->priceAsCents($order->getTotalRefunded());

        return ($newValue - $oldValue);
    }
}
