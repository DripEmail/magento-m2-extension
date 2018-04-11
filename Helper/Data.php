<?php

namespace Drip\Connect\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $customerGroupFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $catalogResourceModelCategoryCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\GroupFactory $customerGroupFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catalogResourceModelCategoryCollectionFactory
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->customerGroupFactory = $customerGroupFactory;
        $this->storeManager = $storeManager;
        $this->catalogResourceModelCategoryCollectionFactory = $catalogResourceModelCategoryCollectionFactory;
        parent::__construct($context);
    }

    /**
     * check if module active
     *
     * @return bool
     */
    public function isModuleActive()
    {
        if (!empty($this->request->getParam('store'))) {
            return (bool)$this->scopeConfig->getValue('dripconnect_general/module_settings/is_enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->request->getParam('store'));
        }

        return (bool)$this->scopeConfig->getValue('dripconnect_general/module_settings/is_enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * consistently format prices as cents
     * strip all except numbers and periods
     *
     * @param $price
     *
     * @return string
     */
    public function priceAsCents($price) {
        return (int) preg_replace("/[^0-9.]/", "", $price) * 100;
    }

    /**
     * Return comma separated string of category names this product is assigned to
     *
     * @param $product
     *
     * @return string
     */
    public function getProductCategoryNames($product) {
        $catIds = $product->getCategoryIds();
        $categoriesString = '';
        $numCategories = count($catIds);
        if($numCategories) {
            $catCollection = $this->catalogResourceModelCategoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', $catIds);

            foreach($catCollection as $category) {
                $categoriesString .= $category->getName() . ', ';
            }
            $categoriesString = substr($categoriesString, 0, -2);
        }

        return $categoriesString;
    }
}
