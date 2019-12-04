<?php

namespace Drip\Connect\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const QUOTE_KEY = 'q';
    const STORE_KEY = 's';
    const SECURE_KEY = 'k';
    const SALT = 'somedefaultsaltstring';

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    /**
     * @var \Magento\Customer\Model\GroupFactory
     */

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /** @var \Magento\Framework\App\State */
    protected $state;

    /** @var \Magento\Framework\App\Response\RedirectInterface */
    protected $redirect;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $catalogResourceModelCategoryCollectionFactory;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    protected $attributeRepository;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catalogResourceModelCategoryCollectionFactory
    ) {
        $this->request = $request;
        $this->configFactory = $configFactory;
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->catalogResourceModelCategoryCollectionFactory = $catalogResourceModelCategoryCollectionFactory;
        $this->attributeRepository = $attributeRepository;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * return brand name for the given product
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getBrandName($product)
    {
        try {
            $attribute = $this->attributeRepository->get(
                \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE,
                'manufacturer'
            );
            $brandName = $product->getAttributeText('manufacturer');
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // attribute does not exist
            $brandName = '';
        }

        return $brandName;
    }

    /**
     * check if module active
     *
     * @return bool
     */
    public function isModuleActive()
    {
        // TODO: This whole method exists to indeterminately figure out scope.
        //       We need to nuke this in favor of always knowing what scope we
        //       are dealing with.
        if (!empty($this->request->getParam('store'))) {
            $config = $this->configFactory->createForCurrentStoreParam();
        } else {
            $config = $this->configFactory->createForCurrentScope();
        }

        return $config->isEnabled();
    }

    /**
     * get store id which is currently being edited
     *
     * @return int
     */
    public function getAdminEditStoreId()
    {
        $storeId = (int) $this->request->getParam('store');

        return $storeId;
    }

    /**
     * consistently format prices as cents
     * strip all except numbers and periods
     *
     * @param $price
     *
     * @return int
     */
    public function priceAsCents($price)
    {
        if (empty($price)) {
            return 0;
        }

        return (int) (preg_replace("/[^0-9.]/", "", $price) * 100);
    }

    /**
     * Return comma separated string of category names this product is assigned to
     *
     * @param $product
     *
     * @return string
     */
    public function getProductCategoryNames($product)
    {
        $catIds = $product->getCategoryIds();
        $categoriesString = '';
        $numCategories = count($catIds);
        if ($numCategories) {
            $catCollection = $this->catalogResourceModelCategoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', $catIds);

            foreach ($catCollection as $category) {
                $categoriesString .= $category->getName() . ', ';
            }
            $categoriesString = substr($categoriesString, 0, -2);
        }

        return $categoriesString;
    }

    /**
     * get request area
     *
     * @return string
     */
    public function getArea()
    {
        if ($this->isApiCall()) {
            return 'API';
        }

        if ($this->state->getAreaCode() == 'adminhtml') {
            return 'Admin';
        }

        return 'Storefront';
    }

    /**
     * check if current call is being done via API
     *
     * @return bool
     */
    protected function isApiCall()
    {
        $regexp = '/^(?:\/index.php)?\/(?:rest|soap)\/(?:\w+)(?:\/|\?wsdl)/i';
        if (preg_match($regexp, $this->request->getRequestUri())) {
            if (!empty($this->redirect->getRefererUrl()) &&
                !empty($this->storeManager->getStore()->getBaseUrl()) &&
                strpos($this->redirect->getRefererUrl(), $this->storeManager->getStore()->getBaseUrl()) === 0) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * @param string $date
     */
    public function formatDate($date)
    {
        $time = new \DateTime($date);
        return $time->format("Y-m-d\TH:i:s\Z");
    }

    /**
     * return salt value
     *
     * @return string
     */
    protected function getSalt()
    {
        $globalConfig = $this->configFactory->createForGlobalScope();
        $salt = $globalConfig->getSalt();
        if (empty(trim($salt))) {
            $salt = self::SALT;
        }

        return $salt;
    }

    /**
     * @param int $quoteId
     * @param int $storeId
     *
     * @return string
     */
    public function getSecureKey($quoteId, $storeId)
    {
        return (substr(hash('sha256', $this->getSalt().$quoteId.$storeId), 0, 32));
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     *
     * @return string
     */
    public function getAbandonedCartUrl($quote)
    {
        return $this->_urlBuilder->getUrl('drip/cart/index', [
            self::QUOTE_KEY => $quote->getId(),
            self::STORE_KEY => $quote->getStoreId(),
            self::SECURE_KEY => $this->getSecureKey($quote->getId(), $quote->getStoreId()),
        ]);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function isEmailValid($email)
    {
        return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
