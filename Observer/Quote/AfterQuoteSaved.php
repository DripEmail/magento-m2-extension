<?php

namespace Drip\Connect\Observer\Quote;

class AfterQuoteSaved extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Framework\Module\ResourceInterface */
    protected $moduleResource;

    /** @var \Magento\Framework\App\ProductMetadataInterface */
    protected $productMetadata;

    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $catalogProductFactory;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Magento\Catalog\Model\Product\Media\ConfigFactory */
    protected $catalogProductMediaConfigFactory;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory
     */
    protected $connectApiCallsHelperSendEventPayloadFactory;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Model\ApiCalls\Helper\SendEventPayloadFactory $connectApiCallsHelperSendEventPayloadFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Registry $registry
    ) {
        $this->connectApiCallsHelperSendEventPayloadFactory = $connectApiCallsHelperSendEventPayloadFactory;
        $this->moduleResource = $moduleResource;
        $this->productMetadata = $productMetadata;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->connectHelper = $connectHelper;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
        parent::__construct($configFactory, $logger);
    }

    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $config = $this->configFactory->createForCurrentScope();
        $quote = $observer->getEvent()->getQuote();


        //////////////////// Generate payload ////////////////////

        $payload = [
            'magento_version' => $this->productMetadata->getVersion(),
            'plugin_version' => $this->moduleResource->getDbVersion('Drip_Connect'),
            'magento_source' => $this->connectHelper->getArea(),
            'event_name' => 'saved_quote',
            'base_object' => [
                'class_name' => get_class($quote),
                'fields' => $quote->getData(),
                'ancillary_data' => [
                    'cart_url' => $this->connectHelper->getAbandonedCartUrl($quote),
                    'is_new' => $this->registry->registry(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW),
                    'guest_checkout_email' => $this->checkoutSession->getGuestEmail(),
                ],
            ],
            'related_objects' => [],
        ];

        foreach ($quote->getAllItems() as $item) {
            $payload['related_objects'][] = [
                'class_name' => get_class($item),
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

        $this->registry->unregister(\Drip\Connect\Helper\Quote::REGISTRY_KEY_IS_NEW);
    }
}
