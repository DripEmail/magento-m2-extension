<?php

namespace Drip\Connect\Model\ApiCalls;

use Laminas\Http\Response;
use \Drip\Connect\Logger\Logger;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Framework\ArchiveFactory;
use \Magento\Framework\Filesystem\DirectoryList;
use \Magento\Framework\Module\ResourceInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Drip\Connect\Model\Http\ClientFactory;
use \Drip\Connect\Model\Configuration;

/**
 * This is a fork of Base.php, to quickly get this going. Eventually, this should all simplify dramatically.
 */
class WooBase extends \Drip\Connect\Model\Restapi\RestapiAbstract
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Drip\Connect\Model\Http\ClientFactory
     */
    protected $connectHttpClientFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * constructor
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        ArchiveFactory $archiveFactory,
        DirectoryList $directory,
        ResourceInterface $moduleResource,
        StoreManagerInterface $storeManager,
        ClientFactory $connectHttpClientFactory,
        Configuration $config,
        $url
    ) {
        parent::__construct(
            $logger,
            $scopeConfig,
            $configWriter,
            $archiveFactory,
            $directory
        );

        $this->storeManager = $storeManager;

        $this->connectHttpClientFactory = $connectHttpClientFactory;
        $this->_responseModel = \Drip\Connect\Model\ApiCalls\Response\Base::class;

        $this->_httpClient = $this->connectHttpClientFactory->create([
            'uri' => $url,
            'config' => [
                'useragent' => self::USERAGENT,
                'timeout' => 30,
            ],
            'logger' => $this->logger,
        ]);

        $version = $moduleResource->getDbVersion('Drip_Connect');

        $this->_httpClient->setHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Drip-Connect-Plugin-Version' => $version
        ]);
    }

    /**
     * Call the API
     *
     * @param \Drip\Connect\Model\Restapi\Request $request
     * @throws \Laminas\Http\Client\Exception\ExceptionInterface
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
        return $this->_createResponse(200, [
            "Status" => "OK",
            "Message" => "Forced Valid Response"
        ]);
    }
    
    protected function _forceInvalidResponse($request)
    {
        return $this->_createResponse(200, [
            "Status" => "OK",
            "Message" => "Forced Invalid Response"
        ]);
    }
    
    protected function _forceError($request)
    {
        return $this->_createResponse(500, [
            "Status" => "Error",
            "Message" => "Forced Error Message"
        ]);
    }
    
    private function _createResponse($statusCode, array $data)
    {
        $response = new Response();
        $response->setStatusCode($statusCode);
        $response->getHeaders()->addHeaderLine("Content-Type", "application/json; charset=utf-8");
        $response->setContent(json_encode($data));
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
