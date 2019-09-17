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
     * @var \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateQuoteFactory
     */
    protected $connectApiCallsHelperCreateUpdateQuoteFactory;

    /**
     * @var \Drip\Connect\Helper\Data
     */
    protected $connectHelper;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $checkoutCartHelper;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $catalogProductFactory;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

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

    /** @var \Magento\Framework\Serialize\Serializer\Json */
    protected $json;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Quote\Model\QuoteFactory $quoteQuoteFactory,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateQuoteFactory $connectApiCallsHelperCreateUpdateQuoteFactory,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Checkout\Helper\Cart $checkoutCartHelper,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\Registry $registry
    ) {
        $this->quoteQuoteFactory = $quoteQuoteFactory;
        $this->connectApiCallsHelperCreateUpdateQuoteFactory = $connectApiCallsHelperCreateUpdateQuoteFactory;
        $this->connectHelper = $connectHelper;
        $this->checkoutCartHelper = $checkoutCartHelper;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
        $this->json = $json;
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

    /**
     * drip actions when send quote to drip 1st time
     *
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function proceedQuoteNew($quote)
    {
        $data = $this->prepareQuoteData($quote);
        $data['action'] = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateQuote::QUOTE_NEW;
        if (count($data['items'])) {
            $this->connectApiCallsHelperCreateUpdateQuoteFactory->create(['data' => $data])->call();
        }
    }

    /**
     * drip actions when send quote to drip from guest checkout, when user enters his email
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return bool
     */
    public function proceedQuoteGuestCheckout($quote, $email)
    {
        $data = $this->prepareQuoteData($quote);
        $data['email'] = $email;
        $data['action'] = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateQuote::QUOTE_NEW;

        $response = $this->connectApiCallsHelperCreateUpdateQuoteFactory->create(['data' => $data])->call();

        return ($response->getResponseCode() == self::SUCCESS_RESPONSE_CODE);
    }

    /**
     * drip actions existing quote gets changed
     *
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function proceedQuote($quote)
    {
        $data = $this->prepareQuoteData($quote);
        $data['action'] = \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateQuote::QUOTE_CHANGED;
        $this->connectApiCallsHelperCreateUpdateQuoteFactory->create(['data' => $data])->call();
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return array
     */
    public function prepareQuoteData($quote)
    {
        $subscriber = $this->subscriberFactory->create()->loadByEmail($this->email);

        $data =  [
            'provider' => \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateQuote::PROVIDER_NAME,
            'email' => $this->email,
            'initial_status' => ($subscriber->isSubscribed() ? 'active' : 'unsubscribed'),
            'cart_id' => $quote->getId(),
            'grand_total' => $this->connectHelper->priceAsCents($quote->getGrandTotal())/100,
            'total_discounts' => $this->connectHelper->priceAsCents(
                (float) $quote->getSubtotal() - (float) $quote->getSubtotalWithDiscount()
            ) / 100,
            'currency' => $quote->getQuoteCurrencyCode(),
            'cart_url' => $this->connectHelper->getAbandonedCartUrl($quote),
            'items' => $this->prepareQuoteItemsData($quote),
            'items_count' => floatval($quote->getItemsQty()),
            'magento_source' => $this->connectHelper->getArea(),
        ];
        return $data;
    }
    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return array
     */
    protected function prepareQuoteItemsData($quote)
    {
        $data =  [];
        foreach ($quote->getAllItems() as $item) {
            $product = $this->catalogProductFactory->create()->load($item->getProduct()->getId());
            $categories = explode(',', $this->connectHelper->getProductCategoryNames($product));
            if (empty($categories)) {
                $categories = [];
            }

            $group = [
                'product_id' => $item->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'categories' => $categories,
                'quantity' => $item->getQty(),
                'price' => $this->connectHelper->priceAsCents($item->getPrice())/100,
                'discounts' => $this->connectHelper->priceAsCents($item->getDiscountAmount())/100,
                'total' => $this->connectHelper->priceAsCents((float)$item->getQty() * (float)$item->getPrice()) / 100,
                'product_url' => $product->getProductUrl(),
                'image_url' => $this->catalogProductMediaConfigFactory->create()->getMediaUrl($product->getThumbnail()),
            ];
            $data[] = $group;
        }

        return $data;
    }

    /**
     * compare orig and new data
     * Data types of data must match or there will be a difference
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return bool
     */
    public function isQuoteChanged($quote)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_OLD_DATA);
        $newData = $this->prepareQuoteData($quote);

        return ($this->json->serialize($oldData) != $this->json->serialize($newData));
    }

    /**
     * check if we know the user's email (need it to track in drip)
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return bool
     */
    public function isUnknownUser($quote)
    {
        $this->email = '';

        if ($quote->getCustomerEmail()) {
            $this->email = $quote->getCustomerEmail();
        } elseif ($email = $this->checkoutSession->getGuestEmail()) {
            $this->email = $email;
        }

        return ! (bool) $this->email;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $oldQuote
     */
    public function recreateCartFromQuote($oldQuote)
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->removeAllItems();
        $quote->merge($oldQuote);
        $quote->collectTotals()->save();
        $this->checkoutSession->setQuoteId($quote->getId());
    }
}
