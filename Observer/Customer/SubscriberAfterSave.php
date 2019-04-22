<?php

namespace Drip\Connect\Observer\Customer;

class SubscriberAfterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $connectCustomerHelper;

    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Drip\Connect\Helper\Customer $connectCustomerHelper
    ) {
        parent::__construct($connectHelper, $registry);
        $this->connectCustomerHelper = $connectCustomerHelper;
        $this->request = $request;
    }
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->connectHelper->isModuleActive()) {
            return;
        }

        $route = $this->request->getRouteName();
        $controller = $this->request->getControllerName();
        $action = $this->request->getActionName();
        $allowedActions = [
            'customer_index_massSubscribe',
            'customer_index_massUnsubscribe',
            'newsletter_subscriber_massUnsubscribe'
        ];

        // unlike to M1 treate all massactions here (from the both newsletters and customers grids)
        // but still avoid to run it on other customer changes
        if (in_array($route . '_' . $controller . '_' . $action, $allowedActions)) {
            $subscriber = $observer->getSubscriber();
            $this->connectCustomerHelper->proceedSubscriberSave($subscriber);
        }
    }
}

