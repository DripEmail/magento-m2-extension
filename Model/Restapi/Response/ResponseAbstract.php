<?php

namespace Drip\Connect\Model\Restapi\Response;

use Laminas\Http\Response as LaminasResponse;

/**
 * Abstract base class for responses.
 */
class ResponseAbstract
{
    /**
     * @var \Laminas\Http\Response Response as received from API
     */
    protected $_response;

    /** @var bool */
    protected $_isError = false;

    /** @var string|null */
    protected $_errorMessage = null;

    /**
     * @param LaminasResponse $response
     * @param string $errorMessage
     */
    public function __construct(LaminasResponse $rawResponse = null, $errorMessage)
    {
        if ($errorMessage) {
            $this->_setError($errorMessage);
        } else {
            $this->_response = $rawResponse ?: new LaminasResponse();
        }
    }

    /**
     * Gets the \Laminas\Http\Response object
     *
     * @return LaminasResponse
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
