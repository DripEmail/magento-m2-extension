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

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
        parent::__construct($configFactory, $logger);
        $this->registry = $registry;
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

        $config = $this->configFactory->createForCurrentScope();

        $email = $this->request->getParam('email');

        $subscriber = $this->subscriberFactory->create()->loadByEmail($email);
        $newSubscriberSubscribed = $subscriber->isSubscribed();

        // We only force subscription status in Drip when subscribed because if
        // the user already exists in Drip and is subscribed there, we don't
        // want to unsubscribe them, because presumably they have opted in
        // elsewhere.
        $this->customerHelper->proceedGuestSubscriberNew($subscriber, $config, $newSubscriberSubscribed);
    }
}
