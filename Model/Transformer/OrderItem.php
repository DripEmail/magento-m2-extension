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

    /**
     * @param bool use normal or orig data
     *
     * @return array
     */
    public function getStatusData($useOrig = false)
    {
        return [
            'status' => ($useOrig ? $this->item->getOrigData('status') : $this->item->getStatus()),
            'qty_backordered' => ($useOrig ? $this->item->getOrigData('qty_backordered') : $this->item->getQtyBackordered()),
            'qty_canceled' => ($useOrig ? $this->item->getOrigData('qty_canceled') : $this->item->getQtyCanceled()),
            'qty_invoiced' => ($useOrig ? $this->item->getOrigData('qty_invoiced') : $this->item->getQtyInvoiced()),
            'qty_ordered' => ($useOrig ? $this->item->getOrigData('qty_ordered') : $this->item->getQtyOrdered()),
            'qty_refunded' => ($useOrig ? $this->item->getOrigData('qty_refunded') : $this->item->getQtyRefunded()),
            'qty_shipped' => ($useOrig ? $this->item->getOrigData('qty_shipped') : $this->item->getQtyShipped()),
        ];
    }
}
