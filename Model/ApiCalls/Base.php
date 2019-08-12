<?php
namespace Drip\Connect\Model\ApiCalls;


class Base
    extends \Drip\Connect\Model\Restapi\RestapiAbstract
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
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\ArchiveFactory $archiveFactory,
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Drip\Connect\Model\Http\ClientFactory $connectHttpClientFactory,
        array $options = []
    ) {
        parent::__construct(
            $logger,
            $scopeConfig,
            $configWriter,
            $dataObjectFactory,
            $archiveFactory,
            $directory
        );

        $this->storeManager = $storeManager;

        $storeId = empty($options['store_id']) ? $this->storeManager->getStore()->getId() : $options['store_id'];
        $this->setStoreId($storeId);

        $this->connectHttpClientFactory = $connectHttpClientFactory;
        if (isset($options['response_model'])) {
            $this->_responseModel = $options['response_model'];
        } else {
            $this->_responseModel = \Drip\Connect\Model\ApiCalls\Response\Base::class;
        }

        if (isset($options['log_filename'])) {
            $this->_logFilename = $options['log_filename'];
        }

        if (isset($options['behavior'])) {
            $this->_behavior = $options['behavior'];
        } else {
            $this->_behavior = $this->scopeConfig->getValue('dripconnect_general/api_settings/behavior', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        }

        if (isset($options['http_client'])) {
            $this->_httpClient = $options['http_client'];
        } else {
            if ($options['endpoint']) {
                $endpoint = $options['endpoint'];
            } else {
                $endpoint = '';
            }
            $url = $this->scopeConfig->getValue('dripconnect_general/api_settings/url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId).$endpoint;
            if (!empty($options['v3'])) {
                $url = str_replace('/v2/', '/v3/', $url);
            }

            $config = array(
                'useragent' => self::USERAGENT,
                'timeout' => $this->scopeConfig->getValue('dripconnect_general/api_settings/timeout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId) / 1000,
            );
            if (!empty($options['config']) && is_array($options['config'])) {
                $config = array_merge($config, $options['config']);
            }

            $this->_httpClient = $this->connectHttpClientFactory->create(['args' => [
                'uri' => $url,
                'config' => $config,
                'logger' => $this->getLogger(),
            ]]);

            $this->_httpClient->setHeaders(array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ));

            $this->_httpClient->setAuth(
                $this->scopeConfig->getValue('dripconnect_general/api_settings/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId),
                '',
                \Zend_Http_Client::AUTH_BASIC
            );
        }
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
        return new \Zend_Http_Response(200, array("Content-type" => "application/json; charset=utf-8"), json_encode(array(
            "Status" => "OK",
            "Message" => "Forced Valid Response"
        )));
    }

    protected function _forceInvalidResponse($request)
    {
        return new \Zend_Http_Response(200, array("Content-type" => "application/json; charset=utf-8"), json_encode(array(
            "Status" => "OK",
            "Message" => "Forced Invalid Response"
        )));
    }

    protected function _forceError($request)
    {
        return new \Zend_Http_Response(500, array("Content-type" => "application/json; charset=utf-8"), json_encode(array(
            "Status" => "Error",
            "Message" => "Forced Error Message"
        )));
    }

    /**
     * @param string response class
     */
    public function setResponseModel($response)
    {
        $this->_responseModel = $response;
    }

}


