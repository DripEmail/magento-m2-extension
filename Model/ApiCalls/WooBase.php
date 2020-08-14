<?php
namespace Drip\Connect\Model\ApiCalls;

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
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\ArchiveFactory $archiveFactory,
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Drip\Connect\Model\Http\ClientFactory $connectHttpClientFactory,
        \Drip\Connect\Model\Configuration $config,
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
