<?php

namespace Drip\Connect\Observer\Customer;

class AfterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerCustomerFactory;

    /** @var \Magento\Framework\Serialize\Serializer\Json */
    protected $json;

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
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory
    ) {
        parent::__construct($configFactory, $logger);
        $this->registry = $registry;
        $this->customerHelper = $customerHelper;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->json = $json;
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

        $config = $this->configFactory->createForCurrentStoreParam();

        if ($this->registry->registry(self::REGISTRY_KEY_CUSTOMER_IS_NEW)) {
            $acceptsMarketing = $this->registry->registry(self::REGISTRY_KEY_NEW_USER_SUBSCRIBE_STATE);
            $this->customerHelper->proceedAccount(
                $customer,
                $config,
                $acceptsMarketing,
                \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_NEW,
                $acceptsMarketing
            );
        } else {
            if ($this->isCustomerChanged($customer)) {
                $this->customerHelper->proceedAccount(
                    $customer,
                    $config,
                    $this->registry->registry(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT),
                    \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_UPDATED,
                    $this->isCustomerStatusChanged($customer)
                );
            }
        }
        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_IS_NEW);
        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
    }

    /**
     * compare orig and new data
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    protected function isCustomerChanged($customer)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
        $newData = $this->customerHelper->prepareCustomerData(
            $customer,
            true,
            false,
            $this->registry->registry(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT)
        );

        return ($this->json->serialize($oldData) != $this->json->serialize($newData));
    }

    // DUP: of SaveAfter
    /**
     * Determine whether the status has changed between the old and new data
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    protected function isCustomerStatusChanged($customer)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
        // TODO: Refactor away stringly typed boolean.
        $oldStatus = $oldData['custom_fields']['accepts_marketing'] == 'yes';
        $newStatus = $this->registry->registry(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT);
        return $oldStatus !== $newStatus;
    }
}
