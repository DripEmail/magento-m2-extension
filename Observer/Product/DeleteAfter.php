<?php

namespace Drip\Connect\Observer\Product;

/**
 * Product after delete observer
 */
class DeleteAfter extends \Drip\Connect\Observer\Base
{
    const PRODUCT_DELETED = 'deleted';
    /** @var \Drip\Connect\Helper\Product */
    protected $productHelper;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Product $productHelper,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->productHelper = $productHelper;
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

        $config = $this->configFactory->createForCurrentScope();

        $this->productHelper->sendEvent(
            $product,
            $config,
            self::PRODUCT_DELETED
        );
    }
}
