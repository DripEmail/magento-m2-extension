<?php

namespace Drip\Connect\Model\ApiCalls;

use \Drip\Connect\Model\Restapi\RestapiAbstract;

/**
 * Restapi base class
 */
class Base extends RestapiAbstract
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Drip\Connect\Model\Http\ClientFactory
     */
    protected $connectHttpClientFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
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
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\ArchiveFactory $archiveFactory,
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Drip\Connect\Model\Http\ClientFactory $connectHttpClientFactory,
        \Drip\Connect\Model\Configuration $config,
        $endpoint,
        $v3 = false
    ) {
        /* 
         * Since the Drip extension is sending data through the WooBase class
         * we are clearing this to prevent issues in other parts of the codebase. 
         */
    }

    /**
     * Call the API
     *
     * @param \Drip\Connect\Model\Restapi\Request $request
     * @throws \Zend_Http_Client_Exception
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
            $this->_httpClient->setRawData($request->getRawData());
        }

        $response = $this->_httpClient->request($request->getMethod());

        $this->_lastRequestUrl = $this->_httpClient->getUri();
        $this->_lastRequest = $this->_httpClient->getLastRequest();

        return $response;
    }

    protected function _forceValidResponse($request)
    {
        return new \Zend_Http_Response(200, ["Content-type" => "application/json; charset=utf-8"], json_encode([
            "Status" => "OK",
            "Message" => "Forced Valid Response"
        ]));
    }

    protected function _forceInvalidResponse($request)
    {
        return new \Zend_Http_Response(200, ["Content-type" => "application/json; charset=utf-8"], json_encode([
            "Status" => "OK",
            "Message" => "Forced Invalid Response"
        ]));
    }

    protected function _forceError($request)
    {
        return new \Zend_Http_Response(500, ["Content-type" => "application/json; charset=utf-8"], json_encode([
            "Status" => "Error",
            "Message" => "Forced Error Message"
        ]));
    }

    /**
     * @param string response class
     */
    public function setResponseModel($response)
    {
        $this->_responseModel = $response;
    }
}
