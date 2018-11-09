<?php

namespace Drip\Connect\Helper;

class Wishlist extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $catalogResourceModelCategoryCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $catalogProductMediaConfig;

    /**
     * @var \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory
     */
    protected $connectApiCallsHelperRecordAnEventFactory;

    /**
     * @var \Drip\Connect\Helper\Data
     */
    protected $connectHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Media\Config $catalogProductMediaConfig,
        \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory $connectApiCallsHelperRecordAnEventFactory,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->storeManager = $storeManager;
        $this->connectApiCallsHelperRecordAnEventFactory = $connectApiCallsHelperRecordAnEventFactory;
        $this->connectHelper = $connectHelper;
        $this->catalogProductMediaConfig = $catalogProductMediaConfig;
        parent::__construct($context);
    }


    /**
     * @param $action
     * @param $customer
     * @param $product
     *
     * @return mixed
     */
    public function doWishlistEvent($action, $customer, $product) {
        return $this->connectApiCallsHelperRecordAnEventFactory->create([
            'data' => [
                'email' => $customer->getEmail(),
                'action' => $action,
                'properties' => [
                    'product_id' => $product->getId(),
                    'categories' => $this->connectHelper->getProductCategoryNames($product),
                    'brand' => $this->connectHelper->getBrandName($product),
                    'name' => $product->getName(),
                    'price' => $this->connectHelper->priceAsCents($product->getFinalPrice()),
                    'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
                    'image_url' => $this->catalogProductMediaConfig->getMediaUrl($product->getThumbnail()),
                    'source' => 'magento'
                ]
            ]
        ])->call();
    }

}
