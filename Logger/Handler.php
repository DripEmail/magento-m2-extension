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

    /**
     * Whether to actually send the logs
     * @var boolean
     */
    public $isEnabled = false;

    /**
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem
     * @param \Drip\Connect\Model\ConfigurationFactory $configFactory,
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @throws \Exception
     */
    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory
    ) {
        // Set whether this is enabled by the customer from their settings.
        $logSettings = $dataObjectFactory->create();
        $logSettings->setData($configFactory->createForGlobalScope->getLogSettings());

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
