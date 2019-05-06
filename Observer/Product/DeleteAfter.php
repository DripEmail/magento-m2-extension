<?php

namespace Drip\Connect\Observer\Product;

class DeleteAfter extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Catalog\Model\ProductRepository */
    protected $productRepository;

    /** @var \Drip\Connect\Helper\Product */
    protected $productHelper;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Drip\Connect\Helper\Product $productHelper,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
        parent::__construct($connectHelper, $registry);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (! $this->connectHelper->isModuleActive()) {
            return;
        }

        $product = $observer->getProduct();

        if (! $product->getId()) {
            return;
        }

        $this->proceedProductDelete($product);
    }

    /**
     * drip actions for product create
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    protected function proceedProductDelete($product)
    {
        $this->productHelper->proceedProductDelete($product);
    }
}

