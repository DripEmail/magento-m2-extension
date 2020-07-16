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

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct($configFactory, $logger);
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
        $email = $this->request->getParam('email');
        $config = $this->configFactory->createForCurrentScope();
        $customer = $this->customerHelper->getCustomerByEmail($email, $config);

        if ($customer === null) {
            return;
        }

        return $this->customerHelper->sendCustomerEvent(
            $customer,
            $this->configFactory,
            Drip\Connect\Helper\Customer::CREATED_ACTION
        );
    }
}
