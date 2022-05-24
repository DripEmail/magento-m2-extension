<?php

namespace Drip\Connect\Model\ApiCalls\Request;

/**
 * Request base class
 */
class Base implements \Drip\Connect\Model\Restapi\Request\RequestInterface
{
    protected $parametersGet = [];

    protected $parametersPost = [];

    protected $rawData = '';

    protected $method = \Zend_Http_Client::GET;

    /**
     * @param array $param
     * @return this
     */
    public function setParametersGet($params)
    {
        $this->parametersGet = $params;

        return $this;
    }

    /**
     * @param array $param
     * @return this
     */
    public function setParametersPost($params)
    {
        $this->parametersPost = $params;

        return $this;
    }

    /**
     * @param string $data
     * @return this
     */
    public function setRawData($data)
    {
        $this->rawData = $data;

        return $this;
    }

    /**
     * @param string $method http request method
     * @return this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return array
     */
    public function getParametersGet()
    {
        return $this->parametersGet;
    }

    /**
     * @return array
     */
    public function getParametersPost()
    {
        return $this->parametersPost;
    }

    /**
     * @return string
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * @return string http request method
     */
    public function getMethod()
    {
        return $this->method;
    }
}
