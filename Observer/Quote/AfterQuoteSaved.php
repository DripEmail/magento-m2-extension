<?php

namespace Drip\Connect\Observer\Quote;

/**
 * After quote saved observer
 */
class AfterQuoteSaved extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Quote */
    protected $connectQuoteHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Quote $connectQuoteHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->connectQuoteHelper = $connectQuoteHelper;
        $this->registry = $registry;
        parent::__construct($configFactory, $logger);
    }

    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $config = $this->configFactory->createForCurrentScope();
        $quote = $observer->getEvent()->getQuote();

        if ($this->registry->registry(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW)) {
            $action = (string) \Drip\Connect\Helper\Quote::CREATED_ACTION;
        } else {
            $action = (string) \Drip\Connect\Helper\Quote::UPDATED_ACTION;
        }

        $this->connectQuoteHelper->sendQuote($quote, $config, $action);

        $this->registry->unregister(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW);
    }
}
