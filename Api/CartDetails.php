<?php
namespace Drip\Connect\Api;

class CartDetails
{
    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $cartRepository;

    /**
     * @var \Drip\Connect\Helper\Data
    */
    protected $connectHelper;

    /**
     * @var \Drip\Connect\Api\CartDetailsResponseFactory
    */
    protected $responseFactory;


    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Drip\Connect\Api\CartDetailsResponseFactory $responseFactory,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->cartRepository = $cartRepository;
        $this->responseFactory = $responseFactory;
				$this->connectHelper = $connectHelper;
    }

    /**
     * GET for cart details
     * @param string $cartId
     * @return \Drip\Connect\Api\CartDetailsResponse
     */
    public function showDetails($cartId) {
        $response = $this->responseFactory->create();
        $quote = $this->cartRepository->get($cartId);
        $url = $this->connectHelper->getAbandonedCartUrl($quote);

        $response->setData(['cart_url' => $url]);

        return $response;
    }
}
