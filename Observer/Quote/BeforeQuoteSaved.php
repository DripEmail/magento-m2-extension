<?php

namespace Drip\Connect\Observer\Quote;

/**
 * Before quote saved observer
 */
class BeforeQuoteSaved extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Framework\Registry */
    protected $registry;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->registry = $registry;
        parent::__construct($configFactory, $logger, $storeManager);
    }

    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        $this->registry->register(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW, $quote->isObjectNew());
    }
}
