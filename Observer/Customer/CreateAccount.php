<?php

namespace Drip\Connect\Observer\Customer;

class CreateAccount extends \Drip\Connect\Observer\Base
{
    const REGISTRY_KEY_NEW_USER_SUBSCRIBE_STATE = 'is_new_user_wants_to_subscribe';

    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct($connectHelper);
        $this->registry = $registry;
        $this->request = $request;
    }

    /**
     * check if customer wants to subscribe while sign up
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $acceptsMarketing = $this->request->getParam('is_subscribed') ? 'yes' : 'no';

        $this->registry->unregister(self::REGISTRY_KEY_NEW_USER_SUBSCRIBE_STATE);
        $this->registry->register(self::REGISTRY_KEY_NEW_USER_SUBSCRIBE_STATE, $acceptsMarketing);
    }
}
