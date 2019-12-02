<?php

namespace Drip\Connect\Observer\Quote;

class BeforeQuoteSaved extends \Drip\Connect\Observer\Base
{
    /**
     * @var \Drip\Connect\Helper\Quote
     */
    protected $connectQuoteHelper;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteQuoteFactory;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Helper\Quote $connectQuoteHelper,
        \Magento\Quote\Model\QuoteFactory $quoteQuoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\Registry $registry
    ) {
        $this->connectQuoteHelper = $connectQuoteHelper;
        $this->quoteQuoteFactory = $quoteQuoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
        parent::__construct($configFactory, $logger);
    }

    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        if ($this->connectQuoteHelper->isUnknownUser($quote)) {
            return;
        }

        if (!$quote->isObjectNew()) {
            $orig = $this->quoteQuoteFactory->create()->load($quote->getId());
            $data = $this->connectQuoteHelper->prepareQuoteData($orig);
            $this->registry->unregister(\Drip\Connect\Helper\Quote::REGISTRY_KEY_OLD_DATA);
            $this->registry->register(\Drip\Connect\Helper\Quote::REGISTRY_KEY_OLD_DATA, $data);
        }

        if (!$this->registry->registry(
            \Drip\Connect\Helper\Quote::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE
        )) {
            $this->registry->unregister(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW);
            if ($quote->getCustomerEmail()) {
                if ($quote->getDrip() || $quote->getCustomerEmail() == $this->checkoutSession->getGuestEmail()) {
                    $this->registry->register(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW, false);
                } else {
                    $this->registry->register(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW, true);
                    $quote->setDrip(true); // only for auth users
                }
            } else {
                if (!$this->checkoutSession->getGuestEmail()) {
                    $this->registry->register(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW, true);
                } else {
                    $this->registry->register(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW, false);
                }
            }
        }
    }
}
