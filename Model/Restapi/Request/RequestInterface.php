<?php

namespace Drip\Connect\Model\Restapi\Request;

interface RequestInterface
{
    /**
     * @param array $params
     * @return this
     */
    public function setParametersGet($params);

    /**
     * @param array $params
     * @return this
     */
    public function setParametersPost($params);

    /**
     * @param string $data
     * @return this
     */
    public function setRawData($data);

    /**
     * @param string $method http request method
     * @return this
     */
    public function setMethod($method);

    /**
     * @return array
     */
    public function getParametersGet();

    /**
     * @return array
     */
    public function getParametersPost();

    /**
     * @return string http request method
     */
    public function getMethod();
}
