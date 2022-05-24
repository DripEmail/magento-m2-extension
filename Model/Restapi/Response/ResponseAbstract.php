<?php

namespace Drip\Connect\Model\Restapi\Response;

/**
 * Abstract base class for responses.
 */
class ResponseAbstract
{
    /**
     * @var \Zend_Http_Response Response as received from API
     */
    protected $_response;

    /** @var bool */
    protected $_isError = false;

    /** @var string|null */
    protected $_errorMessage = null;

    /**
     * @param \Zend_Http_Response $response
     * @param string $errorMessage
     */
    public function __construct(\Zend_Http_Response $rawResponse = null, $errorMessage)
    {
        if ($errorMessage) {
            $this->_setError($errorMessage);
        } else {
            $this->_response = $rawResponse;
        }
    }

    /**
     * Gets the Zend_Http_Response object
     *
     * @return \Zend_Http_Response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->_isError;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    /**
     * @param string $errorMessage
     * @return self
     */
    public function _setError($errorMessage)
    {
        $this->_isError = true;
        $this->_errorMessage = $errorMessage;
        return $this;
    }
}
