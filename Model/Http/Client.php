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
        $requestBody = $this->_prepareBody();
        $requestUrl = $this->getUri(true);
        $response = parent::request($method);
        $responseData = $response->getBody();

        $this->logger->info('Request Url: '.$requestUrl);
        $this->logger->info('Request Body: '.$requestBody);
        $this->logger->info('Response: '.$responseData);

        return $response;
    }
}
