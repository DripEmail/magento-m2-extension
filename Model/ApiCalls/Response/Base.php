<?php

namespace Drip\Connect\Model\ApiCalls\Response;

use Laminas\Http\Response as LaminasResponse;
use Drip\Connect\Model\Restapi\Response\ResponseInterface as RestapiResponseInterface;
use Drip\Connect\Model\Restapi\Response\ResponseAbstract;

/**
 * Response base
 */
class Base extends ResponseAbstract implements RestapiResponseInterface
{
    /** @var array */
    protected $responseData;

    /**
     * constructor
     */
    public function __construct(?LaminasResponse $response = null, ?string $errorMessage = null)
    {
        parent::__construct($response, $errorMessage);

        if (!$this->_isError) {
            $this->responseData = json_decode($this->getResponse()->getBody(), true);
        }
    }

    /**
     * @return string Json response
     */
    public function toJson()
    {
        return $this->getResponse();
    }

    /**
     * @return array
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * Get the HTTP response status code
     *
     * @return int|null
     */
    public function getResponseCode()
    {
        if (empty($this->getResponse())) {
            return null;
        }

        return $this->getResponse()->getStatus();
    }
}
