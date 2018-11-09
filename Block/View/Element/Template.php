<?php
namespace Drip\Connect\Block\View\Element;

class Template extends \Magento\Framework\View\Element\Template
{
    /** @var \Drip\Connect\Helper\Data */
    protected $helper;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Customer\Model\SessionFactory */
    protected $customerSessionFactory;

    /** @var \Magento\Framework\Registry */
    protected $coreRegistry;

    /** @var \Magento\Catalog\Model\Product\Media\ConfigFactory */
    protected $catalogProductMediaConfigFactory;

    public function __construct(
        \Drip\Connect\Helper\Data $helper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->coreRegistry = $coreRegistry;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public function getConfig()
    {
        return $this->_scopeConfig;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getMediaUrl($product)
    {
        return $this->catalogProductMediaConfigFactory->create()->getMediaUrl($product->getThumbnail());
    }

    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    /**
     * @return bool
     */
    public function isModuleActive()
    {
        return $this->helper->isModuleActive();
    }

    /**
     * @return \Drip\Connect\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        if (empty($this->customerSession)) {
            $this->customerSession = $this->customerSessionFactory->create();
        }

        return $this->customerSession->isLoggedIn();
    }

    /**
     * @return string
     */
    public function getCustomerEmail()
    {
        if (empty($this->customerSession)) {
            $this->customerSession = $this->customerSessionFactory->create();
        }
        if ($this->isCustomerLoggedIn()) {
            return $this->customerSession->getCustomerData()->getEmail();
        }

        return '';
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->coreRegistry->registry('product');
    }

    /**
     * return name of the product's brand
     *
     * @return string
     */
    public function getBrandName()
    {
        return $this->helper->getBrandName($this->getProduct());
    }
}
