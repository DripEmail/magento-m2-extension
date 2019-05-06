<?php

namespace Drip\Connect\Observer\Product;

class SaveAfter extends \Drip\Connect\Observer\Base
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

        $product = $this->productRepository->getById($product->getId(), false, $this->connectHelper->getAdminEditStoreId());

        if ($this->registry->registry(\Drip\Connect\Helper\Product::REGISTRY_KEY_IS_NEW)) {
            $this->proceedProductNew($product);
        } else {
            if ($this->isProductChanged($product)) {
                $this->proceedProduct($product);
            }
        }
        $this->registry->unregister(\Drip\Connect\Helper\Product::REGISTRY_KEY_IS_NEW);
        $this->registry->unregister(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA);
    }

    /**
     * compare orig and new data
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return bool
     */
    protected function isProductChanged($product)
    {
        $oldData = $this->registry->registry(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA);
        unset($oldData['occurred_at']);
        $newData = $this->productHelper->prepareData($product);
        unset($newData['occurred_at']);

        return (serialize($oldData) != serialize($newData));
    }

    /**
     * drip actions for product create
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    protected function proceedProductNew($product)
    {
        $this->productHelper->proceedProductNew($product);
    }

    /**
     * drip actions for product change
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    protected function proceedProduct($product)
    {
        $this->productHelper->proceedProduct($product);
    }
}

