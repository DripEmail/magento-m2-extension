<?php
namespace Drip\Connect\Model\Http;

class Client extends \Zend_Http_Client
{
    /** @var \Drip\Connect\Logger\Logger */
    protected $logger;

    public function __construct(array $args = [])
    {
        $uri = isset($args['uri']) ? $args['uri'] : null;
        $config = isset($args['config']) ? $args['config'] : null;
        $this->logger = $args['logger'];
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

        $this->logger->info('['.$requestId.'] Request Url: '.$requestUrl);
        $this->logger->info('['.$requestId.'] Request Body: '.$requestBody);
        $this->logger->info('['.$requestId.'] Response: '.$responseData);

        return $response;
    }
}
