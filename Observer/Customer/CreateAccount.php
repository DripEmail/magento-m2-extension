<?php

namespace Drip\Connect\Observer\Customer;

/**
 * I think this exists only because we don't know whether the user is
 * subscribed before in the admin/saveAfter and saveAfter customer observers.
 * ~wjohnston 2019-08-29
 */

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
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct($connectHelper, $logger);
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
        $acceptsMarketing = (bool) $this->request->getParam('is_subscribed');

        $this->registry->unregister(self::REGISTRY_KEY_NEW_USER_SUBSCRIBE_STATE);
        $this->registry->register(self::REGISTRY_KEY_NEW_USER_SUBSCRIBE_STATE, $acceptsMarketing);
    }
}
