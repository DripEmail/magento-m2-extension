<?php
namespace Drip\Connect\Logger;

use Monolog\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/drip.log';

    /** @var string */
    protected $_logSettingsXpath = 'dripconnect_general/log_settings';

    /**
     * Whether to actually send the logs
     * @var boolean
     */
    public $isEnabled = false;

    /**
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @throws \Exception
     */
    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\DataObjectFactory $dataObjectFactory
    ) {
        // Set whether this is enabled by the customer from their settings.
        $logSettings = $dataObjectFactory->create();
        $logSettings = $scopeConfig->getValue($this->_logSettingsXpath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $this->isEnabled = $logSettings->getIsEnabled();

        parent::__construct($filesystem);
    }

    /**
     * @inheritDoc
     */
    public function write(array $record)
    {
        if ($this->isEnabled) {
            parent::write($record);
        }
    }
}
