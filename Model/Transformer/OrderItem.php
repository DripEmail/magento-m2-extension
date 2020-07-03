<?php

namespace Drip\Connect\Model\Transformer;

class OrderItem
{
    /** @var \Magento\Sales\Model\Order\Item */
    protected $item;

    public function __construct(\Magento\Sales\Model\Order\Item $item)
    {
        $this->item = $item;
    }

    public function getOrigStatusData() {
        return [
            'status' => $this->item->getOrigData('status'),
            'qty_backordered' => $this->item->getOrigData('qty_backordered'),
            'qty_canceled' => $this->item->getOrigData('qty_canceled'),
            'qty_invoiced' => $this->item->getOrigData('qty_invoiced'),
            'qty_ordered' => $this->item->getOrigData('qty_ordered'),
            'qty_refunded' => $this->item->getOrigData('qty_refunded'),
            'qty_shipped' => $this->item->getOrigData('qty_shipped'),
        ];
    }

    public function getLiveStatusData() {
        return [
            'status' => $this->item->getStatus(),
            'qty_backordered' => $this->item->getQtyBackordered(),
            'qty_canceled' => $this->item->getQtyCanceled(),
            'qty_invoiced' => $this->item->getQtyInvoiced(),
            'qty_ordered' => $this->item->getQtyOrdered(),
            'qty_refunded' => $this->item->getQtyRefunded(),
            'qty_shipped' => $this->item->getQtyShipped(),
        ];
    }
}
