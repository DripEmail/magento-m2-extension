<?php

namespace Drip\Connect\Observer\Quote;

class ClearCartOnLogin implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Request\Http $request,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->customerSession = $customerSession;
        $this->request = $request;
        $this->connectHelper = $connectHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (! $this->connectHelper->isModuleActive()) {
            return;
        }

        if ($this->isIgnoreMerge()) {
            return;
        }

        if ($this->customerSession->getIsAbandonedCartGuest()) {
            $observer->getEvent()->getQuote()->removeAllItems();
            $this->customerSession->unsIsAbandonedCartGuest();
        }
    }

    /**
     * check if current handler should be ignored for clear cart on quote merge
     *
     * @return bool
     */
    protected function isIgnoreMerge()
    {
        $route = $this->request->getRouteName();
        $controller = $this->request->getControllerName();
        $action = $this->request->getActionName();

        return in_array($route.'_'.$controller.'_'.$action, [
            'drip_cart_index'
        ]);
    }
}
