<?php

namespace Drip\Connect\Observer\Quote;

class BeforeQuoteSaved implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Drip\Connect\Helper\Data
     */
    protected $connectHelper;

    /**
     * @var \Drip\Connect\Helper\Quote
     */
    protected $connectQuoteHelper;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteQuoteFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Helper\Quote $connectQuoteHelper,
        \Magento\Quote\Model\QuoteFactory $quoteQuoteFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->connectHelper = $connectHelper;
        $this->connectQuoteHelper = $connectQuoteHelper;
        $this->quoteQuoteFactory = $quoteQuoteFactory;
        $this->registry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->connectHelper->isModuleActive()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();

        if ($this->connectQuoteHelper->isUnknownUser($quote)) {
            return;
        }

        if (!$quote->isObjectNew()) {
            $orig = $this->quoteQuoteFactory->create()->load($quote->getId());
            $data = $this->connectQuoteHelper->prepareQuoteData($orig);
            $this->registry->register(\Drip\Connect\Helper\Quote::REGISTRY_KEY_OLD_DATA, $data);
        }

        if (!$this->registry->registry(\Drip\Connect\Helper\Quote::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE)) {
            if (!$quote->getDrip()) {
                $this->registry->register(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW, true);
                $quote->setDrip(true);
            } else {
                $this->registry->register(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW, false);
            }
        }

    }

}