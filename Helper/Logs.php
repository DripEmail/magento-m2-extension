<?php

namespace Drip\Connect\Helper;

class Logs extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SENDLOGS_RESPONSE_OK = 1;
    const SENDLOGS_RESPONSE_FAIL = 2;

    const MAX_TOAL_ZIP_SIZE = 20971520; // 20Mb

    protected $logFiles = [];

    /**
     * constructor
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        $logFiles = []
    ) {
        parent::__construct($context);
        $this->logFiles = $logFiles;
    }

    /**
     * @param int $storeId
     * @throws \Exception
     */
    public function sendLogs($storeId = null)
    {
        //todo main logic
    }
}
