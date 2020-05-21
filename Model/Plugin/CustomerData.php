<?php

namespace Drip\Connect\Model\Plugin;

class CustomerData
{
    /**
     * @var CurrentCustomer
     */
    private $currentCustomer;

    public function __construct(
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
    ) {
        $this->currentCustomer = $currentCustomer;
    }

    public function afterGetSectionData(\Magento\Customer\CustomerData\Customer $subject, $result)
    {
        if ($this->currentCustomer->getCustomerId()) {
            $customer = $this->currentCustomer->getCustomer();
            // Add email to the customerData.get("customer") hash. This'll allow us to fetch it uncached by page caching.
            $result['email'] = $customer->getEmail();
        }

        return $result;
    }
}
