<?php

namespace Drip\Connect\Block\View\Element;

/**
 * Base class for templates
 */
class Template extends \Magento\Framework\View\Element\Template
{
    /** @var \Drip\Connect\Helper\Data */
    protected $helper;

    /** @var \Drip\Connect\Model\Configuration */
    protected $config;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Customer\Model\SessionFactory */
    protected $customerSessionFactory;

    /** @var \Magento\Framework\Registry */
    protected $coreRegistry;

    /** @var \Magento\Catalog\Model\Product\Media\ConfigFactory */
    protected $catalogProductMediaConfigFactory;

    /** @var \Magento\Framework\App\ProductMetadataInterface */
    protected $productMetadata;

    /** @var \Magento\Framework\Module\ResourceInterface */
    protected $moduleResource;

    public function __construct(
        \Drip\Connect\Helper\Data $helper,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helper = $helper;
        $this->config = $configFactory->create($this->getStore()->getId());
        $this->coreRegistry = $coreRegistry;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->moduleResource = $moduleResource;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * @return string
     */
    public function getDripVersion()
    {
        return $this->moduleResource->getDbVersion('Drip_Connect');
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

    public function getAccountParam()
    {
        return $this->config->getAccountParam();
    }

    /**
     * @return bool
     */
    public function isModuleActive()
    {
        return $this->config->getIntegrationToken() !== null;
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
