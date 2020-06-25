<?php
namespace Drip\Connect\Helper;

class Quote extends \Magento\Framework\App\Helper\AbstractHelper
{
    const REGISTRY_KEY_IS_NEW = 'newquote';

    const CREATED_ACTION = 'created';

    const UPDATED_ACTION = 'updated';

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteQuoteFactory;

    /**
     * @var \Drip\Connect\Helper\Data
     */
    protected $connectHelper;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $catalogProductFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Media\ConfigFactory
     */
    protected $catalogProductMediaConfigFactory;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Magento\Framework\App\ProductMetadataInterface */
    protected $productMetadata;

    /** @var \Magento\Framework\Module\ResourceInterface */
    protected $moduleResource;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory */
    protected $connectApiCallsHelperSendEventPayloadFactory;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Quote\Model\QuoteFactory $quoteQuoteFactory,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory $connectApiCallsHelperSendEventPayloadFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
        $this->quoteQuoteFactory = $quoteQuoteFactory;
        $this->connectHelper = $connectHelper;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->checkoutSession = $checkoutSession;
        $this->productMetadata = $productMetadata;
        $this->moduleResource = $moduleResource;
        $this->connectApiCallsHelperSendEventPayloadFactory = $connectApiCallsHelperSendEventPayloadFactory;
        $this->subscriberFactory = $subscriberFactory;
        parent::__construct(
            $context
        );
    }

    public function sendQuote(\Magento\Quote\Model\Quote $quote, \Drip\Connect\Model\Configuration $config, string $action)
    {
      $items = [];
      foreach ($quote->getAllItems() as $item) {
          $itemData = [
            'item_id' => $item->getId(),
            'product_id' => $item->getProduct()->getId(),
          ];
          $items[] = $itemData;
      }

      $payload = [
          'cart_id' => (string) $quote->getId(),
          'items' => $items,
          'action' => $action,
      ];

      $response = $this->connectApiCallsHelperSendEventPayloadFactory->create([
          'config' => $config,
          'payload' => $payload,
      ])->call();
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $oldQuote
     */
    public function recreateCartFromQuote($oldQuote)
     {
         $quote = $this->checkoutSession->getQuote();

         if ($quote->getId() !== $oldQuote->getId()) {
             $quote->removeAllItems();
             $quote->merge($oldQuote);
             $quote->collectTotals()->save();
         }
         $this->checkoutSession->setQuoteId($quote->getId());
     }
}
