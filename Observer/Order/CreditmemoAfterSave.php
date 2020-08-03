<?php

namespace Drip\Connect\Observer\Order;

/**
 * Credit memo after save observer
 */
class CreditmemoAfterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Framework\Registry */
    protected $registry;

    /** @var \Drip\Connect\Model\Transformer\OrderFactory */
    protected $orderTransformerFactory;

    /** @var \Magento\Sales\Api\Data\OrderInterface */
    protected $order;

    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Model\Transformer\OrderFactory $orderTransformerFactory,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($configFactory, $logger, $storeManager);
        $this->connectHelper = $connectHelper;
        $this->registry = $registry;
        $this->orderTransformerFactory = $orderTransformerFactory;
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

        $config = $this->configFactory->create($order->getStoreId());

        /** @var \Drip\Connect\Model\Transformer\Order */
        $orderTransformer = $this->orderTransformerFactory->create([
            'order' => $order,
            'config' => $config,
        ]);

        $orderTransformer->proceedOrderRefund($this->refundDiff($order));
    }

    /**
     * get refund value in cents
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
