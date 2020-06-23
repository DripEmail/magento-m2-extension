<?php

namespace Drip\Connect\Controller\Ajax;

class CheckoutSendCart extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    /** @var \Drip\Connect\Helper\Quote */
    protected $connectQuoteHelper;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Helper\Quote $connectQuoteHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->configFactory = $configFactory;
        $this->connectQuoteHelper = $connectQuoteHelper;
    }

    public function execute()
    {
        $error = 1;
        $errorMessage = __('Email not given');
        $resultJson = $this->resultJsonFactory->create();

        $email = $this->getRequest()->getParam('email');
        if ($email) {
            $quote = $this->checkoutSession->getQuote();
            if (!$quote->getId()) {
                $errorMessage = __("Can't find cart in session");
            } else {

                if ($email != $this->checkoutSession->getGuestEmail()) {
                    $config = $this->configFactory->createForCurrentScope();

                    // TODO: See if we still need this.
                    $result = $this->connectQuoteHelper->sendQuote($quote, $config, \Drip\Connect\Helper\Quote::CREATED_ACTION);
                } else {
                    $result = 1; // do not need to send call
                }

                if ($result) {
                    $error = 0;
                    $errorMessage = '';
                }
            }
        }

        $response = ['error' => $error, 'error_message' => $errorMessage];

        return $resultJson->setData($response);
    }
}
