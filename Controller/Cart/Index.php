<?php

namespace Drip\Connect\Controller\Cart;

/**
 * Cart controller for abandoned cart.
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Drip\Connect\Helper\Quote */
    protected $quoteHelper;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Framework\App\RequestInterface */
    protected $request;

    /** @var \Magento\Store\Api\StoreRepositoryInterface */
    protected $storeRepository;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $quoteRepository;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Drip\Connect\Helper\Quote $quoteHelper,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        parent::__construct($context);
        $this->configFactory = $configFactory;
        $this->connectHelper = $connectHelper;
        $this->quoteHelper = $quoteHelper;
        $this->customerSession = $customerSession;
        $this->request = $context->getRequest();
        $this->storeRepository = $storeRepository;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        $config = $this->configFactory->createForCurrentScope();

        if ($config->getIntegrationToken() === null) {
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        $quoteId = $this->request->getParam(\Drip\Connect\Helper\Data::QUOTE_KEY);
        $storeId = $this->request->getParam(\Drip\Connect\Helper\Data::STORE_KEY);
        $secureKey = $this->request->getParam(\Drip\Connect\Helper\Data::SECURE_KEY);

        if (! $quoteId || ! $storeId || ! $secureKey) {
            $this->messageManager->addError(__('Link is broken'));
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        if ($secureKey !== $this->connectHelper->getSecureKey($quoteId, $storeId)) {
            $this->messageManager->addError(__('Link is broken'));
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        try {
            $store = $this->storeRepository->getById($storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->messageManager->addError(__('Unknown Store'));
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        try {
            $oldQuote = $this->quoteRepository->get($quoteId)->setStore($store);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->messageManager->addError(__('Unknown Cart'));
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        if (! $this->customerSession->isLoggedIn()) {
            $this->customerSession->setIsAbandonedCartGuest(1);
        }

        $this->quoteHelper->recreateCartFromQuote($oldQuote);

        $resultRedirect->setPath('checkout/cart');

        return $resultRedirect;
    }
}
