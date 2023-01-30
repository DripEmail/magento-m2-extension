<?php

namespace Drip\Connect\Helper;

/**
 * Product helpers
 */
class Product extends \Magento\Framework\App\Helper\AbstractHelper
{
    const REGISTRY_KEY_IS_NEW = 'newproduct';
    const REGISTRY_KEY_OLD_DATA = 'oldproductdata';
    const PROVIDER_NAME = 'magento';

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory */
    protected $connectApiCallsHelperSendEventPayloadFactory;

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
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Catalog\Model\Product\Media\ConfigInterface $mediaConfig,
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory $connectApiCallsHelperSendEventPayloadFactory
    ) {
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->connectHelper = $connectHelper;
        $this->connectApiCallsHelperSendEventPayloadFactory = $connectApiCallsHelperSendEventPayloadFactory;
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
            "provider" => self::PROVIDER_NAME,
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
     * Send product created, updated, deleted events to WIS
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Drip\Connect\Model\Configuration $config
     * @param string $action
     */
    public function sendEvent(
        \Magento\Catalog\Model\Product $product,
        \Drip\Connect\Model\Configuration $config,
        string $action
    ) {
        if ($product->getId() === null) {
            return;
        }

        $payload = [
            'product_id' => (string) $product->getId(),
            'action' => $action
        ];

        return $this->connectApiCallsHelperSendEventPayloadFactory->create([
            'config' => $config,
            'payload' => $payload,
        ])->call();
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
