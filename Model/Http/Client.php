<?php
namespace Drip\Connect\Model\Http;

class Client extends \Zend_Http_Client
{
    /** @var \Monolog\Logger */
    protected $logger;

    public function __construct($uri, array $config, \Monolog\Logger $logger)
    {
        $this->logger = $logger;
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
        $requestId = uniqid();
        $this->setHeaders('X-Drip-Connect-Request-Id', $requestId);
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
