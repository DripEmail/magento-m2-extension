<?php

namespace Drip\Connect\Observer\Wishlist;

class AddProduct implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Drip\Connect\Helper\Data
     */
    protected $connectHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Drip\Connect\Helper\Wishlist
     */
    protected $wishlistHelper;


    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Request\Http $request,
        \Drip\Connect\Helper\Wishlist $wishlistHelper
    ) {
        $this->connectHelper = $connectHelper;
        $this->customerSession = $customerSession;
        $this->request = $request;
        $this->wishlistHelper = $wishlistHelper;
    }

    /**
     * Call rest api endpoint with info about customer and product added
     * @param $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $this->customerSession->getCustomer();
        $product = $observer->getProduct();

        $this->wishlistHelper->doWishlistEvent(
            \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_WISHLIST_ADD_PRODUCT,
            $customer,
            $product
        );
    }

}
