<?php

namespace Drip\Connect\Observer\Quote;

class AfterQuoteSaved implements \Drip\Connect\Observer\Base
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
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Drip\Connect\Helper\Quote $connectQuoteHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->connectQuoteHelper = $connectQuoteHelper;
        $this->registry = $registry;
        parent::__construct($connectHelper);
    }

    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        //do nothing
        if ($this->registry->registry(\Drip\Connect\Helper\Quote::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE)) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();

        if ($this->connectQuoteHelper->isUnknownUser($quote)) {
            return;
        }

        if ($this->registry->registry(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW)) {
            $this->connectQuoteHelper->proceedQuoteNew($quote);
        } else {
            $oldData = $this->registry->registry(\Drip\Connect\Helper\Quote::REGISTRY_KEY_OLD_DATA);
            if(empty($oldData['items']) || count($oldData['items']) == 0) {
                //customer logged in previously with empty cart and then adds a product
                $this->connectQuoteHelper->proceedQuoteNew($quote);
            } else {
                if ($this->connectQuoteHelper->isQuoteChanged($quote)) {
                    $this->connectQuoteHelper->proceedQuote($quote);
                }
            }
        }
        $this->registry->unregister(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW);
        $this->registry->unregister(\Drip\Connect\Helper\Quote::REGISTRY_KEY_OLD_DATA);
        $this->registry->unregister(\Drip\Connect\Helper\Quote::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE);
    }

}
