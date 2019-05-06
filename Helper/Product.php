<?php

namespace Drip\Connect\Helper;

class Product extends \Magento\Framework\App\Helper\AbstractHelper
{
    const REGISTRY_KEY_IS_NEW = 'newproduct';
    const REGISTRY_KEY_OLD_DATA = 'oldproductdata';
    const SUCCESS_RESPONSE_CODE = 202;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProductFactory */
    protected $connectApiCallsHelperCreateUpdateProductFactory;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProductFactory $connectApiCallsHelperCreateUpdateProductFactory,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->connectHelper = $connectHelper;
        $this->connectApiCallsHelperCreateUpdateProductFactory = $connectApiCallsHelperCreateUpdateProductFactory;
        parent::__construct($context);
    }

    /**
     * prepare array of product data to send to drip
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    public function prepareData($product)
    {
        $data = [
        ];

        return $data;
    }

    /**
     * drip actions when send product to drip 1st time
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    public function proceedProductNew($product)
    {
        $data = $this->prepareData($product);
        $data['action'] = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProduct::PRODUCT_NEW;
        $this->connectApiCallsHelperCreateUpdateProductFactory->create(['data' => $data])->call();
    }

    /**
     * drip actions when product gets changed
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    public function proceedProduct($product)
    {
        $data = $this->prepareData($product);
        $data['action'] = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProduct::PRODUCT_CHANGED;
        $this->connectApiCallsHelperCreateUpdateProductFactory->create(['data' => $data])->call();
    }

    /**
     * drip actions when product is deleted
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    public function proceedProductDelete($product)
    {
        $data = $this->prepareData($product);
        $data['action'] = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateProduct::PRODUCT_DELETED;
        $this->connectApiCallsHelperCreateUpdateProductFactory->create(['data' => $data])->call();
    }
}
