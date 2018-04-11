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

    /**
     * constructor
     */
    public function __construct(array $options,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Drip\Connect\Model\Http\ClientFactory $connectHttpClientFactory
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->connectHttpClientFactory = $connectHttpClientFactory;
        if (isset($options['response_model'])) {
            $this->_responseModel = $options['response_model'];
        } else {
            $this->_responseModel = 'drip_connect/ApiCalls_Response_Base';
        }

        if (isset($options['log_filename'])) {
            $this->_logFilename = $options['log_filename'];
        }

        if (isset($options['behavior'])) {
            $this->_behavior = $options['behavior'];
        } else {
            $this->_behavior = $this->scopeConfig->getValue('dripconnect_general/api_settings/behavior', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        if (isset($options['http_client'])) {
            $this->_httpClient = $options['http_client'];
        } else {
            if ($options['endpoint']) {
                $endpoint = $options['endpoint'];
            } else {
                $endpoint = '';
            }
            $url = $this->scopeConfig->getValue('dripconnect_general/api_settings/url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE).$endpoint;
            $config = array(
                'useragent' => self::USERAGENT,
                'timeout' => $this->scopeConfig->getValue('dripconnect_general/api_settings/timeout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) / 1000,
            );
            if (!empty($options['config']) && is_array($options['config'])) {
                $config = array_merge($config, $options['config']);
            }

            $this->_httpClient = $this->connectHttpClientFactory->create();

            $this->_httpClient->setHeaders(array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/vnd.api+json'
            ));

            $this->_httpClient->setAuth(
                $this->scopeConfig->getValue('dripconnect_general/api_settings/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
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


