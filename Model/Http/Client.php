<?php
namespace Drip\Connect\Model\Http;

/**
 * Rest client
 */
class Client extends \Zend_Http_Client
{
    /** @var \Monolog\Logger */
    protected $logger;

    /** @var \Drip\Connect\Model\Http\RequestIDFactory */
    protected $requestIdFactory;

    public function __construct(
        $uri,
        array $config,
        \Monolog\Logger $logger,
        \Drip\Connect\Model\Http\RequestIDFactory $requestIdFactory
    ) {
        $this->logger = $logger;
        $this->requestIdFactory = $requestIdFactory;
        parent::__construct($uri, $config);
    }

    /**
     * Send the HTTP request and return an HTTP response object
     *
     * @param string $method
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    public function request($method = null)
    {
        // ID unique to each outgoing API request.
        $requestId = uniqid();
        $this->setHeaders('X-Drip-Connect-Request-Id', $requestId);

        // ID unique to each triggering Magento page load. Useful for
        // debouncing multiple events within a single Magento request.
        $magentoRequestId = $this->requestIdFactory->create()->requestId();
        $this->setHeaders('X-OMS-Request-Id', $magentoRequestId);

        $requestBody = $this->_prepareBody();
        $requestUrl = $this->getUri(true);
        $response = parent::request($method);
        $responseData = $response->getBody();

        $this->logger->info("[{$requestId}] Request Url: {$requestUrl}");
        $this->logger->info("[{$requestId}] Request Body: {$requestBody}");
        $this->logger->info("[{$requestId}] Response: {$responseData}");

        return $response;
    }
}
