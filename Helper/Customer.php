<?php

namespace Drip\Connect\Helper;

class Customer extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Magento\Customer\Model\GroupFactory */
    protected $customerGroupFactory;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\GroupFactory $customerGroupFactory,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
    ) {
        parent::__construct($context);
        $this->customerGroupFactory = $customerGroupFactory;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * prepare array of customer data we use to send in drip
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param bool $updatableOnly leave only those fields which are used in update action
     */
    public function prepareCustomerData($customer, $updatableOnly = true)
    {
        if ($customer->getOrigData() && $customer->getData('email') != $customer->getOrigData('email')) {
            $newEmail = $customer->getData('email');
        } else {
            $newEmail = '';
        }
        $data = array (
            'email' => $customer->getEmail(),
            'new_email' => ($newEmail ? $newEmail : ''),
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'custom_fields' => array(
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'birthday' => $customer->getDob(),
                'gender' => $this->getGenderText($customer->getGender()),
                'magento_account_created' => $customer->getCreatedAt(),
                'magento_customer_group' => $this->customerGroupFactory->create()->load($customer->getGroupId())->getCustomerGroupCode(),
                'magento_store' => (int) $customer->getStoreId(),
                'accepts_marketing' => ($customer->getIsSubscribed() ? 'yes' : 'no'),
            ),
        );

        if ($customer->getDefaultShippingAddress()) {
            $data = array_merge_recursive($data, array('custom_fields'=>$this->getAddressFields($customer->getDefaultShippingAddress())));
        }

        if ($updatableOnly) {
            unset($data['custom_fields']['magento_account_created']);
            unset($data['ip_address']);
        }

        return $data;
    }

    /**
     * get address fields
     *
     * @param \Magento\Customer\Model\Address $address
     */
    public function getAddressFields($address)
    {
        return array (
            'city' => $address->getCity(),
            'state' => $address->getRegion(),
            'zip_code' => $address->getPostcode(),
            'country' => $address->getCountry(),
            'phone_number' => $address->getTelephone(),
        );
    }

    /**
     * @param int $genderCode
     *
     * @return string
     */
    public function getGenderText($genderCode) {
        if ($genderCode == 1) {
            $gender = 'Male';
        } else if ($genderCode == 2) {
            $gender = 'Female';
        } else {
            $gender = '';
        }
        return $gender;
    }
}
