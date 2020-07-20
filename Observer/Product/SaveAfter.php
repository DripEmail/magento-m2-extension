<?php

namespace Drip\Connect\Observer\Product;

/**
 * Product after save observer
 */
class SaveAfter extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Catalog\Model\ProductRepository */
    protected $productRepository;

    /** @var \Drip\Connect\Helper\Product */
    protected $productHelper;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Framework\Serialize\Serializer\Json */
    protected $json;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Product $productHelper,
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
        $this->connectHelper = $connectHelper;
        $this->registry = $registry;
        $this->json = $json;
        parent::__construct($configFactory, $logger, $storeManager);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();

        if (! $product->getId()) {
            return;
        }

        $websiteIds = $product->getWebsiteIds();

        foreach ($websiteIds as $websiteId) {
          $config = $this->configFactory->createFromWebsiteId($websiteId);

          if ($config->getIntegrationToken()) {
            $product = $this->productRepository->getById(
                $product->getId(),
                false,
                $this->connectHelper->getAdminEditStoreId(),
                true
            );

            if ($this->registry->registry(\Drip\Connect\Helper\Product::REGISTRY_KEY_IS_NEW)) {
                $action = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProduct::PRODUCT_NEW;
            } else {
                $action = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProduct::PRODUCT_CHANGED;
            }

            $this->productHelper->sendEvent(
                $product,
                $config,
                $action
            );

            $this->registry->unregister(\Drip\Connect\Helper\Product::REGISTRY_KEY_IS_NEW);
            $this->registry->unregister(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA);
          }
        }
    }

    // /**
    //  * compare orig and new data
    //  *
    //  * @param \Magento\Catalog\Model\Product $product
    //  *
    //  * @return bool
    //  */
    // protected function isProductChanged($product)
    // {
    //     $oldData = $this->registry->registry(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA);
    //     unset($oldData['occurred_at']);
    //     $newData = $this->productHelper->prepareData($product);
    //     unset($newData['occurred_at']);

    //     return ($this->json->serialize($oldData) != $this->json->serialize($newData));
    // }
}
