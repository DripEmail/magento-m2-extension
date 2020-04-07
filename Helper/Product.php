<?php

namespace Drip\Connect\Helper;

class Product extends \Magento\Framework\App\Helper\AbstractHelper
{
    const REGISTRY_KEY_IS_NEW = 'newproduct';
    const REGISTRY_KEY_OLD_DATA = 'oldproductdata';

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProductFactory */
    protected $connectApiCallsHelperCreateUpdateProductFactory;

    /** @var \Magento\CatalogInventory\Api\StockStateInterface */
    protected $stockState;

    /** @var \Magento\Catalog\Model\Product\Media\ConfigInterface */
    protected $mediaConfig;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProductFactory $connectApiCallsHelperCreateUpdateProductFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Catalog\Model\Product\Media\ConfigInterface $mediaConfig,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->connectHelper = $connectHelper;
        $this->connectApiCallsHelperCreateUpdateProductFactory = $connectApiCallsHelperCreateUpdateProductFactory;
        $this->stockState = $stockState;
        $this->mediaConfig = $mediaConfig;
        parent::__construct($context);
    }

    /**
     * prepare array of product data to send to drip
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    public function prepareData($product)
    {
        $categories = explode(',', $this->connectHelper->getProductCategoryNames($product));
        $data = [
            "provider" => \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProduct::PROVIDER_NAME,
            "occurred_at" => $this->connectHelper->formatDate($product->getUpdatedAt()),
            "product_id" => $product->getId(),
            "sku" => $product->getSku(),
            "name" => $product->getName(),
            "price" => $this->connectHelper->priceAsCents($product->getFinalPrice())/100,
            "inventory" => $this->stockState->getStockQty($product->getId()),
            "product_url" => $this->getProductUrl($product),
        ];
        if ($imageUrl = $this->getProductImageUrl($product)) {
            $data["image_url"] = $imageUrl;
        }
        if (count($categories) && !empty($categories[0])) {
            $data["categories"] = $categories;
        }
        if ($brand = $this->connectHelper->getBrandName($product)) {
            $data["brand"] = $brand;
        }

        return $data;
    }

    /**
     * drip actions when send product to drip 1st time
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Drip\Connect\Model\Configuration $config
     */
    public function proceedProductNew(\Magento\Catalog\Model\Product $product, \Drip\Connect\Model\Configuration $config)
    {
        $data = $this->prepareData($product);
        $data['action'] = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProduct::PRODUCT_NEW;

        $this->connectApiCallsHelperCreateUpdateProductFactory->create([
            'config' => $config,
            'data' => $data,
        ])->call();
    }

    /**
     * drip actions when product gets changed
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Drip\Connect\Model\Configuration $config
     */
    public function proceedProduct(\Magento\Catalog\Model\Product $product, \Drip\Connect\Model\Configuration $config)
    {
        $data = $this->prepareData($product);
        $data['action'] = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProduct::PRODUCT_CHANGED;

        $this->connectApiCallsHelperCreateUpdateProductFactory->create([
            'config' => $config,
            'data' => $data,
        ])->call();
    }

    /**
     * drip actions when product is deleted
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Drip\Connect\Model\Configuration $config
     */
    public function proceedProductDelete(\Magento\Catalog\Model\Product $product, \Drip\Connect\Model\Configuration $config)
    {
        $data = $this->registry->registry(\Drip\Connect\Helper\Product::REGISTRY_KEY_OLD_DATA);
        if ($product->getId() == $data['product_id']) {
            $data['action'] = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProduct::PRODUCT_DELETED;
            unset($data['product_url']);

            $this->connectApiCallsHelperCreateUpdateProductFactory->create([
                'config' => $config,
                'data' => $data,
            ])->call();
        }
    }

    /**
     * @param Magento_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getProductUrl($product)
    {
        $needRevert = false;

        if (empty($product->getStoreId())) {
            // if editing is for default scope,
            // temporarily set default store's id to get proper url
            $defaultStoreId = $this->storeManager->getDefaultStoreView()->getId();
            $product->setStoreId($defaultStoreId);
            $needRevert = true;
        }

        $url = $product->getProductUrl(false);

        if ($needRevert) {
            // revert id back to admin's store
            $product->setStoreId(0);
        }

        return $url;
    }

    /**
     * @param Magento_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getProductImageUrl($product)
    {
        $imageUrl = '';

        if ($product->getThumbnail()) {
            $imageUrl = $this->mediaConfig->getMediaUrl($product->getThumbnail());
        }

        return $imageUrl;
    }
}
