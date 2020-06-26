<?php

namespace Drip\Connect\Observer\Quote;

/**
 * Clear cart on login observer.
 */
class ClearCartOnLogin extends \Drip\Connect\Observer\Base
{
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
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Model\ConfigurationFactory $configFactory
    ) {
        $this->customerSession = $customerSession;
        $this->request = $request;
        parent::__construct($configFactory, $logger);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
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
