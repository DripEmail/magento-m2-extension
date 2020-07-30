<?php

namespace Drip\Connect\Observer\Product;

/**
 * Base to handle isActive derived from subscriber store.
 */
abstract class Base extends \Drip\Connect\Observer\Base
{
    /**
     * Base activity on current scope.
     *
     * You need to override this when dealing with ORM observers since they might be called from the admin UI.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     */
    protected function isActive(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();
        if (!$product) {
            return;
        }
        foreach ($product->getWebsiteIds() as $websiteId) {
            if ($this->configFactory->createFromWebsiteId($websiteId)->isActive()) {
                return true;
            }
        }
        return false;
    }
}