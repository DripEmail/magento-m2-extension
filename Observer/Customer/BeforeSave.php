<?php

namespace Drip\Connect\Observer\Customer;

class BeforeSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Drip\Connect\Helper\Quote */
    protected $quoteHelper;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerCustomerFactory;

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
        \Drip\Connect\Helper\Quote $quoteHelper,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory
    ) {
        parent::__construct($configFactory, $logger);
        $this->registry = $registry;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->quoteHelper = $quoteHelper;
        $this->customerHelper = $customerHelper;
    }

    /**
     * - check if customer new
     * - store old customer data (which is used in drip) to compare with later
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();

        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_IS_NEW);
        $this->registry->register(self::REGISTRY_KEY_CUSTOMER_IS_NEW, (bool)$customer->isObjectNew());

        if (!$customer->isObjectNew()) {
            $orig = $this->customerCustomerFactory->create()->load($customer->getId());
            $data = $this->customerHelper->prepareCustomerData(
                $orig,
                true,
                false,
                $this->registry->registry(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE)
            );
            $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
            $this->registry->register(self::REGISTRY_KEY_CUSTOMER_OLD_DATA, $data);
        } else {
            $customer->setDrip(1);
        }
    }
}
