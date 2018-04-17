<?php
namespace Drip\Connect\Helper;


class Quote extends \Magento\Framework\App\Helper\AbstractHelper
{
    const REGISTRY_KEY_IS_NEW = 'newquote';
    const REGISTRY_KEY_OLD_DATA = 'oldquotedata';
    const REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE = 'customercreatedemptycart';

    // if/when we know the user's email, it will be saved here
    protected $email;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteQuoteFactory;

    /**
     * @var \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory
     */
    protected $connectApiCallsHelperRecordAnEventFactory;

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
     * @var \Magento\Catalog\Model\Product\Media\ConfigFactory
     */
    protected $catalogProductMediaConfigFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Quote\Model\QuoteFactory $quoteQuoteFactory,
        \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory $connectApiCallsHelperRecordAnEventFactory,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Checkout\Helper\Cart $checkoutCartHelper,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $catalogProductMediaConfigFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->quoteQuoteFactory = $quoteQuoteFactory;
        $this->connectApiCallsHelperRecordAnEventFactory = $connectApiCallsHelperRecordAnEventFactory;
        $this->connectHelper = $connectHelper;
        $this->checkoutCartHelper = $checkoutCartHelper;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->catalogProductMediaConfigFactory = $catalogProductMediaConfigFactory;
        $this->registry = $registry;
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

        if($this->connectHelper->priceAsCents($quote->getGrandTotal()) == 0) {
            $this->registry->register(self::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE, (bool)$customer->isObjectNew());
        }

    }

    /**
     * drip actions when send quote to drip 1st time
     *
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function proceedQuoteNew($quote)
    {
        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'data' => [
                'email' => $this->email,
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_QUOTE_NEW,
                'properties' => $this->prepareQuoteData($quote),
                ]
       ])->call();
    }
 /**
     * drip actions existing quote gets changed
     *
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function proceedQuote($quote)
    {
        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'data' => [
                'email' => $this->email,
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_QUOTE_CHANGED,
                'properties' => $this->prepareQuoteData($quote),
                ]
       ])->call();
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return array
     */
    public function prepareQuoteData($quote)
    {
        $data = array (
            'amount' => $this->connectHelper->priceAsCents($quote->getGrandTotal()),
            'tax' => $this->connectHelper->priceAsCents($quote->getShippingAddress()->getTaxAmount()),
            'fees' => $this->connectHelper->priceAsCents($quote->getShippingAddress()->getShippingAmount()),
            'discounts' => $this->connectHelper->priceAsCents((100*$quote->getSubtotal() - 100*$quote->getSubtotalWithDiscount())/100),
            'currency' => $quote->getQuoteCurrencyCode(),
            'items_count' => floatval($quote->getItemsQty()),
            'abandoned_cart_url' => $this->checkoutCartHelper->getCartUrl(),
            'line_items' => $this->prepareQuoteItemsData($quote),
        );
        return $data;
    }
    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return array
     */
    protected function prepareQuoteItemsData($quote)
    {
        $data = array ();
        foreach ($quote->getAllItems() as $item) {
            $product = $this->catalogProductFactory->create()->load($item->getProduct()->getId());

            $group = array(
                'product_id' => $item->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'categories' => $this->connectHelper->getProductCategoryNames($product),
                'quantity' => $item->getQty(),
                'price' => $this->connectHelper->priceAsCents($item->getPrice()),
                'amount' => $this->connectHelper->priceAsCents(($item->getQty() * $item->getPrice())),
                'tax' => $this->connectHelper->priceAsCents($item->getTaxAmount()),
                'taxable' => (preg_match('/[123456789]/', $item->getTaxAmount()) ? 'true' : 'false'),
                'discount' => $this->connectHelper->priceAsCents($item->getDiscountAmount()),
                'currency' => $quote->getQuoteCurrencyCode(),
                'product_url' => $product->getProductUrl(),
                'image_url' => $this->catalogProductMediaConfigFactory->create() ->getMediaUrl($product->getThumbnail()),
            );
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

        echo serialize($oldData) . '<br><br>';
        echo serialize($newData);
        //exit;

        return (serialize($oldData) != serialize($newData));
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
        }

        return ! (bool) $this->email;
    }

}