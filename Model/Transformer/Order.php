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

    /** @var \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory */
    protected $connectApiCallsHelperSendEventPayloadFactory;

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
        \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory $connectApiCallsHelperSendEventPayloadFactory,

        \Magento\Sales\Model\Order $order,
        \Drip\Connect\Model\Configuration $config
    ) {
        $this->connectHelper = $connectHelper;
        $this->salesOrderAddressFactory = $salesOrderAddressFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->connectApiCallsHelperSendEventPayloadFactory = $connectApiCallsHelperSendEventPayloadFactory;

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
        $data = [
            'order_id' => (string) $this->order->getIncrementId(),
        ];

        return $data;
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
            'action' => (string) \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_REFUND,
            'order_id' => (string) $this->order->getIncrementId()
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
     * simple check for valid stringage
     * @param  mixed $stuff
     * @return bool
    */
    private function isNotEmpty($stuff) {
        return !empty(trim((string) $stuff));
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
        $foundOrderId = $this->isNotEmpty($this->order->getIncrementId());
        $foundProvider = $this->isNotEmpty(\Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::PROVIDER_NAME);
        $validEmail = $this->connectHelper->isEmailValid($this->order->getCustomerEmail());
        $foundActions = $this->isNotEmpty(\Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_CANCEL) &&
        $this->isNotEmpty(\Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_CHANGE) &&
        $this->isNotEmpty(\Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_FULFILL) &&
        $this->isNotEmpty(\Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_NEW) &&
        $this->isNotEmpty(\Drip\Connect\Model\ApiCalls\Helper\CreateUpdateOrder::ACTION_REFUND);
        return $foundOrderId && $foundProvider && $foundActions && $validEmail;
    }

    public function proceedOrderNew()
    {
        $orderData = $this->getOrderDataNew();

        $caller = $this->connectApiCallsHelperSendEventPayloadFactory->create([
            'config' => $this->config,
            'payload' => $orderData,
        ])->call();
    }

    public function proceedOrderCompleted()
    {
        $orderData = $this->getOrderDataCompleted();

        $response = $this->connectApiCallsHelperSendEventPayloadFactory->create([
            'config' => $this->config,
            'payload' => $orderData,
        ])->call();
    }

    public function proceedOrderCancel()
    {
        $orderData = $this->getOrderDataCanceled();

        $response = $this->connectApiCallsHelperSendEventPayloadFactory->create([
            'config' => $this->config,
            'payload' => $orderData,
        ])->call();
    }

    /**
     * @param int $refundValue
     */
    public function proceedOrderRefund($refundValue)
    {
        $orderData = $this->getOrderDataRefund($refundValue);

        $response = $this->connectApiCallsHelperSendEventPayloadFactory->create([
            'config' => $this->config,
            'payload' => $orderData,
        ])->call();
    }

    public function proceedOrderOther()
    {
        $orderData = $this->getOrderDataOther();

        $response = $this->connectApiCallsHelperSendEventPayloadFactory->create([
            'config' => $this->config,
            'payload' => $orderData,
        ])->call();
    }
}
