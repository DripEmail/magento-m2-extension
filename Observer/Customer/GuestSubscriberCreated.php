<?php

namespace Drip\Connect\Observer\Customer;

/**
 * Guest subscriber created observer
 */
class GuestSubscriberCreated extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($configFactory, $logger, $storeManager);
        $this->request = $request;
        $this->customerHelper = $customerHelper;
        $this->subscriberFactory = $subscriberFactory;
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
        $email = $this->request->getParam('email');
        $config = $this->configFactory->createForCurrentScope();

        $subscriber = $this->subscriberFactory->create()->loadByEmail($email);
        $newSubscriberSubscribed = $subscriber->isSubscribed();

        return $this->customerHelper->sendSubscriberEvent(
            $subscriber,
            $config
        );
    }
}
