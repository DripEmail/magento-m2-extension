<?php

namespace Drip\Connect\Model\Http;

class RequestID
{
    const REQUEST_ID_KEY = 'drip_request_id';

    /** @var \Magento\Framework\Registry */
    protected $registry;

    public function __construct(\Magento\Framework\Registry $registry) {
        $this->registry = $registry;
    }

    public function requestId()
    {
        // Make request "graceful", so it doesn't throw the exception and just
        // short-circuits if it's set already.
        $this->registry->register(self::REQUEST_ID_KEY, $this->requestIdGenerator(), true);

        return $this->registry->registry(self::REQUEST_ID_KEY);
    }

    protected function requestIdGenerator()
    {
        return uniqid("", true);
    }
}
