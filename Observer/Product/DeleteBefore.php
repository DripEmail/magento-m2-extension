<?php

namespace Drip\Connect\Observer\Product;

class DeleteBefore extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Product */
    protected $productHelper;

    /** @var \Magento\Catalog\Model\ProductRepository */
    protected $productRepository;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Product $productHelper,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Registry $registry
    ) {
        $this->productHelper = $productHelper;
        $this->productRepository = $productRepository;
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

        $orig = $this->productRepository->getById($product->getId(), false, $this->connectHelper->getAdminEditStoreId(), true);
        $data = $this->productHelper->prepareData($orig);
        $this->registry->unregister(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA);
        $this->registry->register(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA, $data);
    }
}

