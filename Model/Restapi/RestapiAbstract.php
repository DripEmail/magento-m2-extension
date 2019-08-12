<?php
namespace Drip\Connect\Model\Restapi;

abstract class RestapiAbstract
{
    const USERAGENT = 'Drip Connect M2';

    /** @var string */
    protected $_responseModel;

    /** @var string */
    protected $_logFilename = 'drip.log';

    /**  @var string */
    protected $_behavior;

    /** @var \Zend_Http_Client */
    protected $_httpClient;

    /** @var string */
    protected $_lastRequestUrl;

    /** @var string */
    protected $_lastRequest;

    /** @var \Zend_Http_Response */
    protected $_lastResponse;

    /** @var string */
    protected $_apiName = 'apiclient';

    /** @var string */
    protected $_logSettingsXpath = 'dripconnect_general/log_settings';

    /** @var \Drip\Connect\Logger\Logger */
    protected $logger;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Magento\Framework\Filesystem\DirectoryList */
    protected $directory;

    /** @var \Magento\Framework\ArchiveFactory */
    protected $archiveFactory;

    /** @var \Magento\Framework\DataObjectFactory */
    protected $dataObjectFactory;

    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    protected $configWriter;

    /** @var int */
    protected $storeId = 0;

    public function __construct(
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\ArchiveFactory $archiveFactory,
        \Magento\Framework\Filesystem\DirectoryList $directory
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
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
            $rawResponse = $this->_callApiWithBehaviorConsidered($request);

            $className = $this->_responseModel;
            /** @var \Drip\Connect\Model\Restapi\Response\Abstract $response */
            $response = new $className($rawResponse);
            return $response;
        } catch (\Exception $e) {
            $this->logger->log(\Monolog\Logger::ERROR, $e->__toString());
            $className = $this->_responseModel;
            /** @var \Drip\Connect\Model\Restapi\Response\Abstract $response */
            $response = new $className(null, $e->getMessage());
            return $response;
        }
    }

    /**
     * @param $request
     *
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception If a timeout occurs
     */
    protected function _callApiWithBehaviorConsidered($request)
    {
        switch ($this->_behavior) {
            case \Drip\Connect\Model\Source\Behavior::FORCE_VALID:
                $this->_lastResponse = $this->_forceValidResponse($request);
                break;

            case \Drip\Connect\Model\Source\Behavior::FORCE_INVALID:
                $this->_lastResponse = $this->_forceInvalidResponse($request);
                break;

            case \Drip\Connect\Model\Source\Behavior::FORCE_TIMEOUT:
                $this->_forceTimeout($request);
                break;

            case \Drip\Connect\Model\Source\Behavior::FORCE_ERROR:
                $this->_lastResponse = $this->_forceError($request);
                break;

            case \Drip\Connect\Model\Source\Behavior::FORCE_UNKNOWN_ERROR:
                $this->_lastResponse = $this->_forceUnknownResponse($request);
                break;

            case \Drip\Connect\Model\Source\Behavior::CALL_API:
            default:
                $this->_lastResponse = $this->_callApi($request);
                break;
        }

        return $this->_lastResponse;
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
     * @return \Zend_Http_Response
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
     * @throws \Zend_Http_Client_Exception
     */
    abstract protected function _callApi($request);

    /**
     * Force a valid response
     *
     * @param $request
     * @throws \Zend_Http_Client_Exception
     */
    abstract protected function _forceValidResponse($request);

    /**
     * Force an invalid response
     *
     * @param $request
     * @throws \Zend_Http_Client_Exception
     */
    abstract protected function _forceInvalidResponse($request);

    /**
     * Force an error
     *
     * @param $request
     * @throws \Zend_Http_Client_Exception
     */
    abstract protected function _forceError($request);

    /**
     * Force a timeout
     *
     * @param $request
     * @throws \Zend_Http_Client_Exception
     */
    protected function _forceTimeout($request)
    {
        $this->_httpClient->setConfig(array('timeout' => .0001));
        $this->_httpClient->request();
    }

    /**
     * Force unknown response
     *
     * This is a malformed or unexpected response from the API.
     *
     * @param $request
     * @return \Zend_Http_Response
     */
    protected function _forceUnknownResponse($request)
    {
        $httpStatusCode = 200;
        $headers = array();
        $responseBody = "This is an unknown response.";
        return new \Zend_Http_Response($httpStatusCode, $headers, $responseBody);
    }

    public function getLogSettings()
    {
        $settings = $this->dataObjectFactory->create();
        $settings->setData($this->scopeConfig->getValue($this->_logSettingsXpath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId));
        return $settings;
    }

    protected function getLogFile()
    {
        $period = 1 * 60 * 60 * 24;
        $logDir = $this->directory->getPath('log') . DIRECTORY_SEPARATOR . 'drip';
        if (!is_dir($logDir)) {
            mkdir($logDir);
            chmod($logDir, 0777);
        }
        $logDir .= DIRECTORY_SEPARATOR . $this->_apiName;
        if (!is_dir($logDir)) {
            mkdir($logDir);
            chmod($logDir, 0777);
        }
        $archiveDir = $logDir . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR;
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir);
            chmod($archiveDir, 0777);
        }
        $logFile = $logDir . DIRECTORY_SEPARATOR . $this->_logFilename;
        $lastCreation = $this->getLogSettings()->getLastLogArchive();
        if (is_file($logFile) && $period && $lastCreation + $period < time()) {
            //leave default scope for this setting b/c we use one log file for all stores
            $this->configWriter->save($this->_logSettingsXpath.'/last_log_archive', time());
            $archive = $this->archiveFactory->create();
            $archive->pack($logFile, $archiveDir.'archive'.date('Y-m-d-H-i-s').'.tgz');
            unlink($logFile);
        }
        return $logFile;
    }

}
