<?php

namespace Drip\Connect\Model\Transformer;

use Drip\Connect\Helper\Data;
use Magento\Sales\Model\Order\AddressFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Media\ConfigFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory;
use Magento\Sales\Model\Order as ModelOrder;
use Drip\Connect\Model\Configuration;

/**
 * Order Transformer
 */
class Order
{
    const FULFILLMENT_NO = 'not_fulfilled';
    const FULFILLMENT_PARTLY = 'partially_fulfilled';
    const FULFILLMENT_YES = 'fulfilled';

    const PROVIDER_NAME = 'magento';
    const ACTION_NEW = 'placed';
    const ACTION_CHANGE = 'updated';
    const ACTION_PAID = 'paid'; // not used?
    const ACTION_FULFILL = 'fulfilled';
    const ACTION_REFUND = 'refunded';
    const ACTION_CANCEL = 'canceled';

    /** @var Data */
    protected $connectHelper;

    /** @var AddressFactory */
    protected $salesOrderAddressFactory;

    /** @var ProductFactory */
    protected $catalogProductFactory;

    /** @var ConfigFactory */
    protected $catalogProductMediaConfigFactory;

    /** @var SubscriberFactory */
    protected $subscriberFactory;

    /** @var SendEventPayloadFactory */
    protected $connectApiCallsHelperSendEventPayloadFactory;

    /** @var Order */
    protected $order;

    /** @var Configuration */
    protected $config;

    public function __construct(
        Data $connectHelper,
        AddressFactory $salesOrderAddressFactory,
        ProductFactory $catalogProductFactory,
        ConfigFactory $catalogProductMediaConfigFactory,
        SubscriberFactory $subscriberFactory,
        SendEventPayloadFactory $connectApiCallsHelperSendEventPayloadFactory,
        ModelOrder $order,
        Configuration $config
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
     * @return ModelOrder
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
        $data['action'] = (string) self::ACTION_NEW;

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
        $data['action'] = (string) self::ACTION_FULFILL;

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
        $data['action'] = (string) self::ACTION_CANCEL;

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
            'action' => (string) self::ACTION_REFUND,
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
        $data['action'] = (string) self::ACTION_CHANGE;

        return $data;
    }

    /**
     * simple check for valid stringage
     * @param  mixed $stuff
     * @return bool
     */
    private function isNotEmpty($stuff)
    {
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
        $foundProvider = $this->isNotEmpty(self::PROVIDER_NAME);
        $validEmail = $this->connectHelper->isEmailValid($this->order->getCustomerEmail());
        $foundActions = $this->isNotEmpty(self::ACTION_CANCEL) &&
        $this->isNotEmpty(self::ACTION_CHANGE) &&
        $this->isNotEmpty(self::ACTION_FULFILL) &&
        $this->isNotEmpty(self::ACTION_NEW) &&
        $this->isNotEmpty(self::ACTION_REFUND);
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
