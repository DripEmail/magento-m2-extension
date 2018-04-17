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

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Drip\Connect\Helper\Quote $quoteHelper,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory
    ) {
        parent::__construct($connectHelper, $registry);
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
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->connectHelper->isModuleActive()) {
            return;
        }

        $customer = $observer->getCustomer();

        $this->registry->registry(self::REGISTRY_KEY_CUSTOMER_IS_NEW, (bool)$customer->isObjectNew());

        if (!$customer->isObjectNew()) {
            $orig = $this->customerCustomerFactory->create()->load($customer->getId());
            $data = $this->customerHelper->prepareCustomerData($orig);
            $this->registry->register(self::REGISTRY_KEY_CUSTOMER_OLD_DATA, $data);
        } else {
            //this is needed for M1, but not M2 as it causes duplicate checkout updated calls
            //$this->quoteHelper->checkForEmptyQuote($customer);
        }
    }
}
