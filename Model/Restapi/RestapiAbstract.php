<?php

namespace Drip\Connect\Model\Restapi;

use Laminas\Http\Response;
use Drip\Connect\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\ArchiveFactory;
use Magento\Framework\Filesystem\DirectoryList;

abstract class RestapiAbstract
{
    const USERAGENT = 'Drip Connect M2';

    /** @var string */
    protected $_responseModel;

    /** @var \Laminas\Http\Client */
    protected $_httpClient;

    /** @var string */
    protected $_lastRequestUrl;

    /** @var string */
    protected $_lastRequest;

    /** @var \Laminas\Http\Response */
    protected $_lastResponse;

    /** @var Logger */
    protected $logger;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var DirectoryList */
    protected $directory;

    /** @var ArchiveFactory */
    protected $archiveFactory;

    /** @var WriterInterface */
    protected $configWriter;

    /** @var int */
    protected $storeId = 0;

    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        ArchiveFactory $archiveFactory,
        DirectoryList $directory
    ) {
        $this->archiveFactory = $archiveFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->directory = $directory;
    }

    /**
     * Makes API call and returns response object.
     *
     * @param $request
     * @return \Drip\Connect\Model\Restapi\Response\Interface
     */
    public function callApi($request)
    {
        if (!$this->_responseModel) {
            throw new \RuntimeException('Response model must be set.');
        }

        try {
            $rawResponse = $this->_callApi($request);

            $className = $this->_responseModel;
            /** @var \Drip\Connect\Model\Restapi\Response\Abstract $response */
            $response = new $className($rawResponse);
            return $response;
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            $className = $this->_responseModel;
            /** @var \Drip\Connect\Model\Restapi\Response\Abstract $response */
            $response = new $className(null, $e->getMessage());
            return $response;
        }
    }

    /**
     * @return string
     */
    public function getLastRequestUrl()
    {
        return $this->_lastRequestUrl;
    }

    /**
     * @return string
     */
    public function getLastRequest()
    {
        return $this->_lastRequest;
    }

    /**
     * @return \Laminas\Http\Response
     */
    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    /**
     * @param int $storeId
     */
    protected function setStoreId(int $storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Call the API
     *
     * @param $request
     * @throws \Laminas\Http\Client\Exception\ExceptionInterface
     */
    abstract protected function _callApi($request);

    /**
     * Force a valid response
     *
     * @param $request
     * @throws \Laminas\Http\Client\Exception\ExceptionInterface
     */
    abstract protected function _forceValidResponse($request);

    /**
     * Force an invalid response
     *
     * @param $request
     * @throws \Laminas\Http\Client\Exception\ExceptionInterface
     */
    abstract protected function _forceInvalidResponse($request);

    /**
     * Force an error
     *
     * @param $request
     * @throws \Laminas\Http\Client\Exception\ExceptionInterface
     */
    abstract protected function _forceError($request);

    /**
     * Force a timeout
     *
     * @param $request
     * @throws \Laminas\Http\Client\Exception\ExceptionInterface
     */
    protected function _forceTimeout($request)
    {
        $this->_httpClient->setConfig(['timeout' => .0001]);
        $this->_httpClient->request();
    }

    /**
     * Force unknown response
     *
     * This is a malformed or unexpected response from the API.
     *
     * @param $request
     * @return \Laminas\Http\Response
     */
    protected function _forceUnknownResponse($request)
    {
        $httpStatusCode = 200;
        $headers = [];
        $responseBody = "This is an unknown response.";

        $response = new Response();
        $response->setStatusCode($httpStatusCode);
        $response->getHeaders()->addHeaders($headers);
        $response->setContent($responseBody);

        return $response;
    }
}
