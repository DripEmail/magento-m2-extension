<?php

namespace Drip\Connect\Observer\Product;

/**
 * Product before save observer
 */
class SaveBefore extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Catalog\Model\ProductRepository */
    protected $productRepository;

    /** @var \Drip\Connect\Helper\Product */
    protected $productHelper;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Drip\Connect\Helper\Product $productHelper,
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
        $this->connectHelper = $connectHelper;
        $this->registry = $registry;
        parent::__construct($configFactory, $logger, $storeManager);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();

        $this->registry->unregister(\Drip\Connect\Helper\Product::REGISTRY_KEY_IS_NEW);
        $this->registry->register(\Drip\Connect\Helper\Product::REGISTRY_KEY_IS_NEW, (bool) $product->isObjectNew());

        if (!$product->isObjectNew()) {
            $orig = $this->productRepository->getById(
                $product->getId(),
                false,
                $this->connectHelper->getAdminEditStoreId()
            );
            $data = $this->productHelper->prepareData($orig);
            $this->registry->unregister(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA);
            $this->registry->register(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA, $data);
        }
    }
}
