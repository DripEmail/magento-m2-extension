<?php

namespace Drip\Connect\Helper;

class Order extends \Magento\Framework\App\Helper\AbstractHelper
{
    const FULFILLMENT_NO = 'not_fulfilled';
    const FULFILLMENT_PARTLY = 'partially_fulfilled';
    const FULFILLMENT_YES = 'fulfilled';

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Sales\Model\Order\AddressFactory */
    protected $salesOrderAddressFactory;

    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $catalogProductFactory;

    /** @var \Magento\Catalog\Model\Product\Media\ConfigFactory */
    protected $catalogProductMediaConfigFactory;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrderFactory */
    protected $connectApiCallsHelperCreateUpdateOrderFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\Batches\OrdersFactory */
    protected $connectApiCallsHelperBatchesOrdersFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Sales\Model\Order\AddressFactory $salesOrderAddressFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrderFactory $connectApiCallsHelperCreateUpdateOrderFactory,
        \Drip\Connect\Model\ApiCalls\Helper\Batches\OrdersFactory $connectApiCallsHelperBatchesOrdersFactory
    ) {
        $this->connectHelper = $connectHelper;
        $this->salesOrderAddressFactory = $salesOrderAddressFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->connectApiCallsHelperCreateUpdateOrderFactory = $connectApiCallsHelperCreateUpdateOrderFactory;
        $this->connectApiCallsHelperBatchesOrdersFactory = $connectApiCallsHelperBatchesOrdersFactory;
        parent::__construct($context);
    }

    /**
     * prepare array of order data we use to send in drip for new orders
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getCommonOrderData($order)
    {
        $subscriber = $this->subscriberFactory->create()->loadByEmail($order->getCustomerEmail());

        $data = [
            'provider' => (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::PROVIDER_NAME,
            'email' => (string) $order->getCustomerEmail(),
            'initial_status' => ($subscriber->isSubscribed() ? 'active' : 'unsubscribed'),
            'order_id' => (string) $order->getIncrementId(),
            'order_public_id' => (string) $order->getIncrementId(),
            'grand_total' => $this->connectHelper->priceAsCents($order->getGrandTotal()) / 100,
            'total_discounts' => $this->connectHelper->priceAsCents($order->getDiscountAmount()) / 100,
            'total_taxes' => $this->connectHelper->priceAsCents($order->getTaxAmount()) / 100,
            'total_shipping' => $this->connectHelper->priceAsCents($order->getShippingAmount()) / 100,
            'currency' => (string) $order->getOrderCurrencyCode(),
            'occurred_at' => (string) $this->connectHelper->formatDate($order->getUpdatedAt()),
            'items' => $this->getOrderItemsData($order),
            'billing_address' => $this->getOrderBillingData($order),
            'shipping_address' => $this->getOrderShippingData($order),
            'items_count' => floatval($order->getTotalQtyOrdered()),
            'magento_source' => (string) $this->connectHelper->getArea(),
        ];

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for new orders
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getOrderDataNew($order)
    {
        $data = $this->getCommonOrderData($order);
        $data['action'] = (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_NEW;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for full/partly completed orders
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getOrderDataCompleted($order)
    {
        $data = $this->getCommonOrderData($order);
        $data['action'] = (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_FULFILL;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for canceled orders
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getOrderDataCanceled($order)
    {
        $data = $this->getCommonOrderData($order);
        $data['action'] = (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_CANCEL;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for full/partly refunded orders
     *
     * @param \Magento\Sales\Model\Order $order
     * @param int $refundValue
     *
     * @return array
     */
    public function getOrderDataRefund($order, $refundValue)
    {
        $refunds = $order->getCreditmemosCollection();
        $refund = $refunds->getLastItem();
        $refundId = $refund->getIncrementId();

        $data = [
            'provider' => (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateRefund::PROVIDER_NAME,
            'email' => (string) $order->getCustomerEmail(),
            'action' => (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_REFUND,
            'order_id' => (string) $order->getIncrementId(),
            'order_public_id' => (string) $order->getIncrementId(),
            'occurred_at' => (string) $this->connectHelper->formatDate($order->getUpdatedAt()),
            'grand_total' => $this->connectHelper->priceAsCents($order->getGrandTotal()) / 100,
            'refund_amount' => $refundValue / 100,
        ];

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for all other order states
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getOrderDataOther($order)
    {
        $data = $this->getCommonOrderData($order);
        $data['action'] = (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_CHANGE;

        return $data;
    }

    /**
     * check fullfilment state of an order
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return string
     */
    protected function getOrderFulfillment($order)
    {
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_COMPLETE) {
            return self::FULFILLMENT_YES;
        }

        foreach ($order->getAllItems() as $item) {
            if ($item->getStatus() == 'Shipped') {
                return self::FULFILLMENT_PARTLY;
            }
        }

        return self::FULFILLMENT_NO;
    }

    /**
     * get order's billing address data
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    protected function getOrderBillingData($order)
    {
        $addressId = $order->getBillingAddressId();

        return $this->getOrderAddressData($addressId);
    }

    /**
     * get order's shipping address data
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    protected function getOrderShippingData($order)
    {
        $addressId = $order->getShippingAddressId();

        return $this->getOrderAddressData($addressId);
    }

    /**
     * get address data
     *
     * @param int address id
     *
     * @return array
     */
    protected function getOrderAddressData($addressId)
    {
        $address = $this->salesOrderAddressFactory->create()->load($addressId);

        return [
            'first_name' => (string) $address->getFirstname(),
            'last_name' => (string) $address->getLastname(),
            'company' => (string) $address->getCompany(),
            'address_1' => (string) $address->getStreetLine(1),
            'address_2' => (string) $address->getStreetLine(2),
            'city' => (string) $address->getCity(),
            'state' => (string) $address->getRegion(),
            'postal_code' => (string) $address->getPostcode(),
            'country' => (string) $address->getCountryId(),
            'phone' => (string) $address->getTelephone(),
            'email' => (string) $address->getEmail(),
        ];
    }

    /**
     * get order's items data
     *
     * @param \Magento\Sales\Model\Order $order
     * @param bool $isRefund
     *
     * @return array
     */
    protected function getOrderItemsData($order, $isRefund = false)
    {
        $childItems = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId() === null) {
                continue;
            }

            $childItems[$item->getParentItemId()] = $item;
        }

        $data = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $productVariantItem = $item;
            if ($item->getProductType() === 'configurable' && \array_key_exists($item->getId(), $childItems)) {
                $productVariantItem = $childItems[$item->getId()];
            }

            $group = [
                'product_id' => (string) $item->getProductId(),
                'product_variant_id' => (string) $productVariantItem->getProductId(),
                'sku' => (string) $item->getSku(),
                'name' => (string) $item->getName(),
                'quantity' => (float) $item->getQtyOrdered(),
                'price' => $this->connectHelper->priceAsCents($item->getPrice()) / 100,
                'discounts' => $this->connectHelper->priceAsCents($item->getDiscountAmount()) / 100,
                'total' => $this->connectHelper->priceAsCents(
                    (float) $item->getQtyOrdered() * (float) $item->getPrice()
                ) / 100,
                'taxes' => $this->connectHelper->priceAsCents($item->getTaxAmount()) / 100,
            ];
            if ($item->getProduct() !== null) {
                $product = $this->catalogProductFactory->create()->load($item->getProductId());
                $productCategoryNames = $this->connectHelper->getProductCategoryNames($product);
                $categories = explode(',', $productCategoryNames);
                if ($productCategoryNames === '' || empty($categories)) {
                    $categories = [];
                }
                $group['categories'] = $categories;
                $group['product_url'] = (string) $item->getProduct()->getProductUrl();
                $group['image_url'] = (string) $this->catalogProductMediaConfigFactory->create()->getMediaUrl(
                    $product->getThumbnail()
                );
            }
            if ($isRefund) {
                $group['refund_amount'] = $this->connectHelper->priceAsCents($item->getAmountRefunded());
                $group['refund_quantity'] = $item->getQtyRefunded();
            }
            $data[] = $group;
        }

        return $data;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @param bool use normal or orig data
     *
     * @return array
     */
    public function getOrderItemStatusData($item, $useOrig = false)
    {
        return [
            'status' => ($useOrig ? $item->getOrigData('status') : $item->getStatus()),
            'qty_backordered' => ($useOrig ? $item->getOrigData('qty_backordered') : $item->getQtyBackordered()),
            'qty_canceled' => ($useOrig ? $item->getOrigData('qty_canceled') : $item->getQtyCanceled()),
            'qty_invoiced' => ($useOrig ? $item->getOrigData('qty_invoiced') : $item->getQtyInvoiced()),
            'qty_ordered' => ($useOrig ? $item->getOrigData('qty_ordered') : $item->getQtyOrdered()),
            'qty_refunded' => ($useOrig ? $item->getOrigData('qty_refunded') : $item->getQtyRefunded()),
            'qty_shipped' => ($useOrig ? $item->getOrigData('qty_shipped') : $item->getQtyShipped()),
        ];
    }

    /**
     * check if given order can be sent to drip
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function isCanBeSent($order)
    {
        return $this->connectHelper->isEmailValid($order->getCustomerEmail());
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function proceedOrderNew($order)
    {
        $orderData = $this->getOrderDataNew($order);
        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'data' => $orderData
        ])->call();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function proceedOrderCompleted($order)
    {
        $orderData = $this->getOrderDataCompleted($order);
        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'data' => $orderData
        ])->call();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function proceedOrderCancel($order)
    {
        $orderData = $this->getOrderDataCanceled($order);
        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'data' => $orderData
        ])->call();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param int $refundValue
     */
    public function proceedOrderRefund($order, $refundValue)
    {
        $orderData = $this->getOrderDataRefund($order, $refundValue);
        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'data' => $orderData
        ])->call();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function proceedOrderOther($order)
    {
        $orderData = $this->getOrderDataOther($order);
        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'data' => $orderData
        ])->call();
    }

    /**
     * batch orders update
     *
     * @param array $batch
     * @param int $storeId
     *
     * @return \Drip\Connect\Model\Restapi\Response\ResponseAbstract
     */
    public function proceedOrderBatch($batch, $storeId)
    {
        return $this->connectApiCallsHelperBatchesOrdersFactory->create([
            'data' => [
                'batch' => $batch,
                'store_id' => $storeId,
            ]
        ])->call();
    }
}
