<?php

namespace Drip\Connect\Observer\Customer;

class GuestSubscriberCreated extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
        parent::__construct($connectHelper, $registry);
        $this->subscriberFactory = $subscriberFactory;
        $this->request = $request;
        $this->customerHelper = $customerHelper;
    }

    /**
     * guest subscribe on site
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        if (! $this->registry->registry(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER)) {
            return;
        }

        $email = $this->request->getParam('email');

        $subscriber = $this->subscriberFactory->create()->loadByEmail($email);

        $this->customerHelper->proceedGuestSubscriberNew($subscriber);
    }
}
