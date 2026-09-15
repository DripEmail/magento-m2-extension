<?php

namespace Drip\Connect\Model\Restapi\Response;

interface ResponseInterface
{
    /**
     * @return string Json response
     */
    public function toJson();

    /**
     * Gets the response
     *
     * @return \Laminas\Http\Response
     */
    public function getResponse();

    /**
     * @return bool
     */
    public function isError();

    /**
     * @return string|null
     */
    public function getErrorMessage();
}
