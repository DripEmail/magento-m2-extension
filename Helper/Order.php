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

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Sales\Model\Order\AddressFactory $salesOrderAddressFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrderFactory $connectApiCallsHelperCreateUpdateOrderFactory
    ) {
        $this->connectHelper = $connectHelper;
        $this->salesOrderAddressFactory = $salesOrderAddressFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->connectApiCallsHelperCreateUpdateOrderFactory = $connectApiCallsHelperCreateUpdateOrderFactory;
        parent::__construct($context);
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
        $data = array(
            'email' => $order->getCustomerEmail(),
            'provider' => \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::PROVIDER_NAME,
            'upstream_id' => $order->getIncrementId(),
            'identifier' => $order->getIncrementId(),
            'amount' => $this->connectHelper->priceAsCents($order->getGrandTotal()),
            'tax' => $this->connectHelper->priceAsCents($order->getTaxAmount()),
            'fees' => $this->connectHelper->priceAsCents($order->getShippingAmount()),
            'discount' => $this->connectHelper->priceAsCents($order->getDiscountAmount()),
            'currency_code' => $order->getOrderCurrencyCode(),
            'items' => $this->getOrderItemsData($order),
            'billing_address' => $this->getOrderBillingData($order),
            'shipping_address' => $this->getOrderShippingData($order),
            'properties' => array(
                'magento_source' => $this->connectHelper->getArea(),
            ),
        );

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
        $data = array(
            'email' => $order->getCustomerEmail(),
            'provider' => \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::PROVIDER_NAME,
            'upstream_id' => $order->getIncrementId(),
            'amount' => $this->connectHelper->priceAsCents($order->getGrandTotal()),
            'fulfillment_state' => $this->getOrderFulfillment($order),
            'billing_address' => $this->getOrderBillingData($order),
            'shipping_address' => $this->getOrderShippingData($order),
        );

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

        return array(
            'name' => $address->getName(),
            'first_name' => $address->getFirstname(),
            'last_name' => $address->getLastname(),
            'company' => $address->getCompany(),
            'address_1' => $address->getStreet1(),
            'address_2' => $address->getStreet2(),
            'city' => $address->getCity(),
            'state' => $address->getRegion(),
            'zip' => $address->getPostcode(),
            'country' => $address->getCountryId(),
            'phone' => $address->getTelephone(),
            'email' => $address->getEmail(),
        );
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
        $data = array();
        foreach ($order->getAllItems() as $item) {
            $product = $this->catalogProductFactory->create()->load($item->getProduct()->getId());
            $group = array(
                'product_id' => $item->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'quantity' => $item->getQtyOrdered(),
                'price' => $this->connectHelper->priceAsCents($item->getPrice()),
                'amount' => $this->connectHelper->priceAsCents($item->getQtyOrdered() * $item->getPrice()),
                'tax' => $this->connectHelper->priceAsCents($item->getTaxAmount()),
                'taxable' => (preg_match('/[123456789]/', $item->getTaxAmount()) ? 'true' : 'false'),
                'discount' => $this->connectHelper->priceAsCents($item->getDiscountAmount()),
                'properties' => array(
                    'product_url' => $item->getProduct()->getProductUrl(),
                    'product_image_url' => $this->catalogProductMediaConfigFactory->create() ->getMediaUrl($product->getThumbnail()),
                ),
            );
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
}
