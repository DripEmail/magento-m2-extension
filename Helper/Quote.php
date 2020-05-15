<?php
namespace Drip\Connect\Helper;

class Quote extends \Magento\Framework\App\Helper\AbstractHelper
{
    const REGISTRY_KEY_IS_NEW = 'newquote';

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

    public function sendRawQuote(\Magento\Quote\Model\Quote $quote, \Drip\Connect\Model\Configuration $config, string $email = null, array $ancillary_data = [])
    {
        /** @var \Magento\Newsletter\Model\Subscriber */
        $subscriber = $this->subscriberFactory->create()->loadByEmail($email ?? $this->checkoutSession->getGuestEmail() ?? $quote->getCustomerEmail());

        //////////////////// Generate payload ////////////////////
        $payload = [
            'magento_version' => $this->productMetadata->getVersion(),
            'plugin_version' => $this->moduleResource->getDbVersion('Drip_Connect'),
            'magento_source' => $this->connectHelper->getArea(),
            'event_name' => 'saved_quote',
            'base_object' => [
                'class_name' => get_class($quote),
                'resource_name' => $quote->getResourceName(),
                'fields' => $quote->getData(),
                'ancillary_data' => \array_merge([
                    'cart_url' => $this->connectHelper->getAbandonedCartUrl($quote),
                    'guest_checkout_email' => $this->checkoutSession->getGuestEmail(),
                    'provided_email' => $email,
                ], $ancillary_data),
            ],
            'related_objects' => [],
        ];

        if ($subscriber->getId()) {
            $payload['related_objects'][] = [
                'class_name' => get_class($subscriber),
                'resource_name' => $subscriber->getResourceName(),
                'fields' => $subscriber->getData(),
            ];
        }

        // All items includes both parent and child products.
        foreach ($quote->getAllItems() as $item) {
            $payload['related_objects'][] = [
                'class_name' => get_class($item),
                'resource_name' => $item->getResourceName(),
                'fields' => $item->getData(),
            ];
            $product = $this->catalogProductFactory->create()->load($item->getProduct()->getId());
            if ($product) {
                // Categories
                $productCategoryNames = explode(',', $this->connectHelper->getProductCategoryNames($product));
                if ($productCategoryNames === '' || empty($productCategoryNames)) {
                    $productCategoryNames = [];
                }

                // Image
                $productImage = $product->getImage();
                if (!empty($productImage)) {
                    $productImage = $this->catalogProductMediaConfigFactory->create()->getMediaUrl($productImage);
                }

                $payload['related_objects'][] = [
                    'class_name' => get_class($product),
                    'resource_name' => $product->getResourceName(),
                    'fields' => $product->getData(),
                    'ancillary_data' => [
                        'product_catalog_names' => $productCategoryNames,
                        'image' => $productImage,
                    ],
                ];
            }
        }





        //////////////////// Send payload ////////////////////

        // TODO: Log responses.
        $response = $this->connectApiCallsHelperSendEventPayloadFactory->create([
            'config' => $config,
            'payload' => $payload,
        ])->call();
    }

    /**
     * @todo Consider moving this into the cart controller.
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
