<?php

namespace Drip\Connect\Observer\Wishlist;

class PredispatchWishlistIndexRemove implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Drip\Connect\Helper\Data
     */
    protected $connectHelper;

    /**
     * @var \Drip\Connect\Helper\Wishlist
     */
    protected $wishlistHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Wishlist\Model\ItemFactory
     */
    protected $wishlistItemFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $catalogProductFactory;


    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Helper\Wishlist $wishlistHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Wishlist\Model\ItemFactory $wishlistItemFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory
    ) {
        $this->connectHelper = $connectHelper;
        $this->wishlistHelper = $wishlistHelper;
        $this->customerSession = $customerSession;
        $this->request = $request;
        $this->wishlistItemFactory = $wishlistItemFactory;
        $this->catalogProductFactory = $catalogProductFactory;
    }

    /**
     * Call rest api endpoint with info about customer and product removed
     * @param $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->connectHelper->isModuleActive()) {
            return;
        }

        $wishlistItemId = filter_var($this->request->getParam('item'), FILTER_SANITIZE_NUMBER_INT);
        if($wishlistItemId){

            $wishlistItem = $this->wishlistItemFactory->create()->load($wishlistItemId);
            $product = $this->catalogProductFactory->create()->load($wishlistItem->getProductId());
            $customer = $this->customerSession->getCustomer();

            $this->wishlistHelper->doWishlistEvent(
                \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_WISHLIST_REMOVE_PRODUCT,
                $customer,
                $product
            );
        }
    }

}