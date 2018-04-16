<?php

namespace Drip\Connect\Helper;

class Order extends \Magento\Framework\App\Helper\AbstractHelper
{
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
     * @param \Magento\Sales\Model\Order $order
     */
    public function proceedOrderNew($order)
    {
        $orderData = $this->getOrderDataNew($order);
        $this->connectApiCallsHelperCreateUpdateOrderFactory->create([
            'data' => $orderData
        ])->call();
    }
}
