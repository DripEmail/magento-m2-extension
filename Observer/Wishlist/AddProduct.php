<?php

namespace Drip\Connect\Observer\Wishlist;

/**
 * Wishlist add product observer.
 */
class AddProduct extends \Drip\Connect\Observer\Base
{
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
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Request\Http $request,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Wishlist $wishlistHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($configFactory, $logger, $storeManager);
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
        // $customer = $this->customerSession->getCustomer();
        // $product = $observer->getProduct();

        // $config = $this->configFactory->createForCurrentScope();

        // $this->wishlistHelper->doWishlistEvent(
        //     \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_WISHLIST_ADD_PRODUCT,
        //     $config,
        //     $customer,
        //     $product
        // );
    }
}
