<?php

namespace Drip\Connect\Model\Transformer;

class Order
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

    /** @var \Magento\Sales\Model\Order */
    protected $order;

    /** @var \Drip\Connect\Model\Configuration */
    protected $config;

    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Sales\Model\Order\AddressFactory $salesOrderAddressFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrderFactory $connectApiCallsHelperCreateUpdateOrderFactory,

        \Magento\Sales\Model\Order $order,
        \Drip\Connect\Model\Configuration $config
    ) {
        $this->connectHelper = $connectHelper;
        $this->salesOrderAddressFactory = $salesOrderAddressFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->connectApiCallsHelperCreateUpdateOrderFactory = $connectApiCallsHelperCreateUpdateOrderFactory;

        $this->order = $order;
        $this->config = $config;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * prepare array of order data we use to send in drip for new orders
     *
     * @return array
     */
    protected function getCommonOrderData()
    {
        // TODO: We might want to get the subscriber by ID instead of email for better support of multi-store.
        $subscriber = $this->subscriberFactory->create()->loadByEmail($this->order->getCustomerEmail());

        $data = [
            'provider' => (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::PROVIDER_NAME,
            'email' => (string) $this->order->getCustomerEmail(),
            'initial_status' => ($subscriber->isSubscribed() ? 'active' : 'unsubscribed'),
            'order_id' => (string) $this->order->getIncrementId(),
            'order_public_id' => (string) $this->order->getIncrementId(),
            'grand_total' => $this->connectHelper->priceAsCents($this->order->getGrandTotal()) / 100,
            'total_discounts' => $this->connectHelper->priceAsCents($this->order->getDiscountAmount()) / 100,
            'total_taxes' => $this->connectHelper->priceAsCents($this->order->getTaxAmount()) / 100,
            'total_shipping' => $this->connectHelper->priceAsCents($this->order->getShippingAmount()) / 100,
            'currency' => (string) $this->order->getOrderCurrencyCode(),
            'occurred_at' => (string) $this->connectHelper->formatDate($this->order->getUpdatedAt()),
            'items' => $this->getOrderItemsData(),
            'billing_address' => $this->getOrderBillingData(),
            'items_count' => floatval($this->order->getTotalQtyOrdered()),
            'magento_source' => (string) $this->connectHelper->getArea(),
        ];

        // The following differs from M1 based on Magento's documentation that states that M2 will
        // will only present shipping information to the end user if an order contains non-virtual
        // products.
        if($this->hasPhysicalProduct()) {
            $data['shipping_address'] = $this->getOrderShippingData();
        }

        return $data;
    }

    /**
     * determine if this order only has virtual products or not
     * @return boolean
    */
    protected function hasPhysicalProduct() {
        foreach ($this->order->getAllItems() as $item) {
            if($item->getProductType() != 'virtual') { return true; }
        }
        return false;
    }

    /**
     * prepare array of order data we use to send in drip for new orders
     *
     * @return array
     */
    public function getOrderDataNew()
    {
        $data = $this->getCommonOrderData();
        $data['action'] = (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_NEW;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for full/partly completed orders
     *
     * @return array
     */
    protected function getOrderDataCompleted()
    {
        $data = $this->getCommonOrderData();
        $data['action'] = (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_FULFILL;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for canceled orders
     *
     * @return array
     */
    protected function getOrderDataCanceled()
    {
        $data = $this->getCommonOrderData();
        $data['action'] = (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_CANCEL;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for full/partly refunded orders
     *
     * @param int $refundValue
     *
     * @return array
     */
    protected function getOrderDataRefund($refundValue)
    {
        $refunds = $this->order->getCreditmemosCollection();
        $refund = $refunds->getLastItem();
        $refundId = $refund->getIncrementId();

        $data = [
            'provider' => (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateRefund::PROVIDER_NAME,
            'email' => (string) $this->order->getCustomerEmail(),
            'action' => (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_REFUND,
            'order_id' => (string) $this->order->getIncrementId(),
            'order_public_id' => (string) $this->order->getIncrementId(),
            'occurred_at' => (string) $this->connectHelper->formatDate($this->order->getUpdatedAt()),
            'grand_total' => $this->connectHelper->priceAsCents($this->order->getGrandTotal()) / 100,
            'refund_amount' => $refundValue / 100,
        ];

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for all other order states
     *
     * @return array
     */
    protected function getOrderDataOther()
    {
        $data = $this->getCommonOrderData();
        $data['action'] = (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_CHANGE;

        return $data;
    }

    /**
     * get order's billing address data
     *
     * @return array
     */
    protected function getOrderBillingData()
    {
        if ( $this->order->getCustomerIsGuest() ) {
            $address = $this->order->getBillingAddress();
            return $this->buildOrderAddressDataStructure($address);
        }
        $addressId = $this->order->getBillingAddressId();
        return $this->getOrderAddressData( $addressId );
    }

    /**
     * get order's shipping address data
     *
     * @return array
     */
    protected function getOrderShippingData()
    {
        if ( $this->order->getCustomerIsGuest() ) {
            $address = $this->order->getShippingAddress();
            return $this->buildOrderAddressDataStructure( $address );
        }
        $addressId = $this->order->getShippingAddressId();
        return $this->getOrderAddressData( $addressId );
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
        if($addressId === null) {
            return;
        }

        $address = $this->salesOrderAddressFactory->create()->load($addressId);
        return $this->buildOrderAddressDataStructure($address);
    }

    /**
     * helper function for forOrder<*>Data
     *
     * @param \Magento\Customer\Model\Address $address
     * 
     * @return array
     */
    protected function buildOrderAddressDataStructure(\Magento\Sales\Model\Order\Address $address)
    {
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
     * @param bool $isRefund
     *
     * @return array
     */
    protected function getOrderItemsData($isRefund = false)
    {
        $childItems = [];
        foreach ($this->order->getAllItems() as $item) {
            if ($item->getParentItemId() === null) {
                continue;
            }

            $childItems[$item->getParentItemId()] = $item;
        }

        $data = [];
        foreach ($this->order->getAllVisibleItems() as $item) {
            $productVariantItem = $item;
            if ($item->getProductType() === 'configurable' && \array_key_exists($item->getId(), $childItems)) {
                $productVariantItem = $childItems[$item->getId()];
            }

            $group = [
                'product_id' => (string) $item->getProductId(),
                'product_variant_id' => (string) $productVariantItem->getProductId(),
                'sku' => (string) $item->getSku(),
                'name' => trim((string) $item->getName()) ?: $this->generateDefaultProductName($item, $productVariantItem),
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
     * generate a default product name based on id and variant id
     * @param mixed $order_item, $variant_item
     * @return string
     */
    private function generateDefaultProductName($order_item, $variant_item)
    {
        $product_id = (string) $order_item->getProductId() ?: '0';
        $variant_id = (string) $variant_item->getProductId() ?: '0';

        return '[Missing Product ' . $product_id . '-' . $variant_id . ' Name]';
    }
    
    /**
     * simple check for valid stringage
     * @param  mixed $stuff
     * @return bool
    */
    private function isNotEmpty($stuff) {
        return !empty(trim($stuff));
    }

    /**
     * check if given order can be sent to drip
     *
     * @return bool
     */
    public function isCanBeSent()
    {
        /*for shopper activity, the following are required for minimum viability:
         * action, email -or- person_id, provider, order_id
         *   or
         * action, person_id, provider, order_id
         * 
         * person_id is never used in the plugin, so we don't need to worry about the conditional
        */
        $foundOrderId = $this->isNotEmpty((string) $this->order->getIncrementId());
        $foundProvider = $this->isNotEmpty((string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::PROVIDER_NAME);
        $validEmail = $this->connectHelper->isEmailValid($this->order->getCustomerEmail());
        $foundActions = $this->isNotEmpty((string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_CANCEL) &&
        $this->isNotEmpty((string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_CHANGE) &&
        $this->isNotEmpty((string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_FULFILL) &&
        $this->isNotEmpty((string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_NEW) &&
        $this->isNotEmpty((string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_REFUND);        
        return $foundOrderId && $foundProvider && $foundActions && $validEmail;
    }


    public function proceedOrderNew()
    {
        $orderData = $this->getOrderDataNew();

        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'config' => $this->config,
            'data' => $orderData
        ])->call();
    }

    public function proceedOrderCompleted()
    {
        $orderData = $this->getOrderDataCompleted();

        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'config' => $this->config,
            'data' => $orderData
        ])->call();
    }

    public function proceedOrderCancel()
    {
        $orderData = $this->getOrderDataCanceled();

        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'config' => $this->config,
            'data' => $orderData
        ])->call();
    }

    /**
     * @param int $refundValue
     */
    public function proceedOrderRefund($refundValue)
    {
        $orderData = $this->getOrderDataRefund($refundValue);

        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'config' => $this->config,
            'data' => $orderData
        ])->call();
    }

    public function proceedOrderOther()
    {
        $orderData = $this->getOrderDataOther();

        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'config' => $this->config,
            'data' => $orderData
        ])->call();
    }
}
