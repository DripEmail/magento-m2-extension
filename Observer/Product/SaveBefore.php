<?php

namespace Drip\Connect\Observer\Product;

class SaveBefore extends \Drip\Connect\Observer\Base
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
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();

        $this->registry->unregister(\Drip\Connect\Helper\Product::REGISTRY_KEY_IS_NEW);
        $this->registry->register(\Drip\Connect\Helper\Product::REGISTRY_KEY_IS_NEW, (bool) $product->isObjectNew());

        if (! $product->isObjectNew()) {
            $orig = $this->productRepository->getById($product->getId(), false, $this->connectHelper->getAdminEditStoreId());
            $data = $this->productHelper->prepareData($orig);
            $this->registry->unregister(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA);
            $this->registry->register(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA, $data);
        } else {
            //will be needed if we create historical sync for products
            //$product->setDrip(1);
        }
    }
}

