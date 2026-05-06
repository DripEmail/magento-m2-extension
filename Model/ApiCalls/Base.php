<?php

namespace Drip\Connect\Model\ApiCalls;

use Drip\Connect\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\ArchiveFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Drip\Connect\Model\Http\ClientFactory;
use Drip\Connect\Model\Configuration;
use \Drip\Connect\Model\Restapi\RestapiAbstract;
use Laminas\Http\Response;
use Laminas\Http\Client\Exception\ExceptionInterface;

/**
 * Restapi base class
 */
class Base extends RestapiAbstract
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ClientFactory
     */
    protected $connectHttpClientFactory;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * constructor
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param ArchiveFactory $archiveFactory
     * @param DirectoryList $directory
     * @param StoreManagerInterface $storeManager
     * @param ClientFactory $connectHttpClientFactory
     * @param Configuration $config
     * @param String $endpoint
     * @param String $v3 API version
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        ArchiveFactory $archiveFactory,
        DirectoryList $directory,
        StoreManagerInterface $storeManager,
        ClientFactory $connectHttpClientFactory,
        Configuration $config,
        $endpoint,
        $v3 = false
    ) {
        parent::__construct(
            $logger,
            $scopeConfig,
            $configWriter,
            $archiveFactory,
            $directory
        );
        /*
         * Since the Drip extension is sending data through the WooBase class
         * we are clearing this to prevent issues in other parts of the codebase.
         */
    }

    /**
     * Call the API
     *
     * @param \Drip\Connect\Model\Restapi\Request $request
     * @throws ExceptionInterface
     */
    protected function _callApi($request)
    {
        if (!empty($request->getParametersGet())) {
            $this->_httpClient->setParameterGet($request->getParametersGet());
        }
        if (!empty($request->getParametersPost())) {
            $this->_httpClient->setParameterPost($request->getParametersPost());
        }
        if (!empty($request->getRawData())) {
            $this->_httpClient->setRawBody($request->getRawData());
        }

        $response = $this->_httpClient->request($request->getMethod());

        $this->_lastRequestUrl = $this->_httpClient->getUri();
        $this->_lastRequest = $this->_httpClient->getRequest();

        return $response;
    }

    protected function _forceValidResponse($request)
    {
        $response = new Response();
        $response->setStatusCode(200);
        $response->getHeaders()->addHeaderLine("Content-Type", "application/json; charset=utf-8");
        $response->setContent(json_encode([
            "Status" => "OK",
            "Message" => "Forced Valid Response"
        ]));
        return $response;
    }

    protected function _forceInvalidResponse($request)
    {
        $response = new Response();
        $response->setStatusCode(200);
        $response->getHeaders()->addHeaderLine("Content-Type", "application/json; charset=utf-8");
        $response->setContent(json_encode([
            "Status" => "OK",
            "Message" => "Forced Invalid Response"
        ]));
        return $response;
    }

    protected function _forceError($request)
    {
        $response = new Response();
        $response->setStatusCode(500);
        $response->getHeaders()->addHeaderLine("Content-Type", "application/json; charset=utf-8");
        $response->setContent(json_encode([
            "Status" => "Error",
            "Message" => "Forced Error Message"
        ]));
        return $response;
    }

    /**
     * @param string response class
     */
    public function setResponseModel($response)
    {
        $this->_responseModel = $response;
    }
}
