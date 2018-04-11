<?php
namespace Drip\Connect\Model\Http;

class Client extends \Zend_Http_Client
{
    /** @var \Zend_Log */
    protected $_logger;

    public function __construct($args)
    {
        $uri = isset($args['uri']) ? $args['uri'] : null;
        $config = isset($args['config']) ? $args['config'] : null;
        $this->_logger = isset($args['logger']) ? $args['logger'] : null;
        parent::__construct($uri, $config);
    }

    public function getLogger()
    {
        return $this->_logger;
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

        if (!is_null($this->getLogger())) {
            $this->getLogger()->info('Request Url: '.$requestUrl);
            $this->getLogger()->info('Request Body: '.$requestBody);
            $this->getLogger()->info('Response: '.$responseData);
        }

        return $response;
    }
}
