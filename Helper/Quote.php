<?php
namespace Drip\Connect\Helper;

class Quote extends \Magento\Framework\App\Helper\AbstractHelper
{
    const REGISTRY_KEY_IS_NEW = 'newquote';
    const REGISTRY_KEY_OLD_DATA = 'oldquotedata';
    const REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE = 'customercreatedemptycart';
    const SUCCESS_RESPONSE_CODE = 202;

    // if/when we know the user's email, it will be saved here
    protected $email;

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

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /** @var \Magento\Framework\App\ProductMetadataInterface */
    protected $productMetadata;

    /** @var \Magento\Framework\Module\ResourceInterface */
    protected $moduleResource;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory */
    protected $connectApiCallsHelperSendEventPayloadFactory;

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
        \Magento\Framework\Registry $registry // TODO: Get rid of this.
    ) {
        $this->quoteQuoteFactory = $quoteQuoteFactory;
        $this->connectHelper = $connectHelper;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
        $this->productMetadata = $productMetadata;
        $this->moduleResource = $moduleResource;
        $this->connectApiCallsHelperSendEventPayloadFactory = $connectApiCallsHelperSendEventPayloadFactory;
        parent::__construct(
            $context
        );
    }

    /**
     * If customer registers during checkout, they will login, but quote has not been updated with customer info yet
     * so we can't fire "checkout created" on the quote b/c it's not yet assigned to the customer.  Doesn't matter
     * anyway since they've already place an order.
     *
     * When customer logs in or registers, magento creates an empty quote right away.  We don't want to call
     * checkout created on this action, so we check the quote total to avoid firing any quote related events.
     *
     * @param $customer
     */
    public function checkForEmptyQuote($customer)
    {
        //gets active quote for customer, but troube is quote hasn't been updated with this customer info yet
        $quote = $this->quoteQuoteFactory->create()->loadByCustomer($customer);

        if ($this->connectHelper->priceAsCents($quote->getGrandTotal()) == 0) {
            $this->registry->register(self::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE, 1);
        }
    }

    public function sendRawQuote(\Magento\Quote\Model\Quote $quote, \Drip\Connect\Model\Configuration $config, string $email = null, array $ancillary_data = [])
    {
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
