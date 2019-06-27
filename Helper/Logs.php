<?php

namespace Drip\Connect\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Logs extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SENDLOGS_RESPONSE_OK = 1;
    const SENDLOGS_RESPONSE_FAIL = 2;

    const MAX_TOAL_ZIP_SIZE = 20971520; // 20Mb

    /** @var array */
    protected $logFiles = [];

    /** @var \Magento\Framework\App\Filesystem\DirectoryList */
    protected $directoryList;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Framework\Translate\Inline\StateInterface */
    protected $inlineTranslation;

    /** @var \Drip\Connect\Model\Mail\TransportBuilder */
    protected $transportBuilder;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        DirectoryList $directoryList,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Drip\Connect\Model\Mail\TransportBuilder $transportBuilder,
        $logFiles = []
    ) {
        parent::__construct($context);
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->logFiles = $logFiles;
    }

    /**
     * @param int $storeId
     * @throws \Exception
     */
    public function sendLogs($storeId = null)
    {
        /**
         * @var array [
         *      [
         *          'path' => log file path,
         *          'zip_path' => tmp zip file path,
         *          'size' => zip file size (bytes),
         *      ],
         *      [...],
         *      [...],
         * ]
         */
        $dataToSend = [];

        $logFolder = $this->directoryList->getPath(DirectoryList::LOG) . '/';

        foreach ($this->logFiles as $logFile) {

            $logFile = $logFolder . $logFile;

            if (file_exists($logFile)) {

                $zip = new \ZipArchive;

                $zipPath = $logFolder . basename($logFile).'.zip';

                if (file_exists($zipPath)) {
                    $zipOpen = $zip->open($zipPath, \ZipArchive::OVERWRITE);
                } else {
                    $zipOpen = $zip->open($zipPath, \ZipArchive::CREATE);
                }

                if ($zipOpen !== TRUE) {
                    $this->logger->info("can't create zip file for ".$zipPath);
                    continue;
                }

                $zip->addFile($logFile, basename($logFile));
                $zip->close();

                $dataToSend[] = [
                    'path' => $logFile,
                    'zip_path' => $zipPath,
                    'size' => filesize($zipPath),
                ];
            }
        }

        if (!count($dataToSend)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Logs not found'));
        }

        do {
            $total = 0;
            foreach ($dataToSend as $fileData) {
                $total += $fileData['size'];
            }

            if ($total > self::MAX_TOAL_ZIP_SIZE) {
                unset($dataToSend[count($dataToSend)-1]);
            }
        } while ($total > self::MAX_TOAL_ZIP_SIZE);

        $this->sendMail($dataToSend);
    }

    /**
     * send email
     *
     * @param array $dataToSend
     */
    public function sendMail($dataToSend)
    {
        $senderData = [
            'name' => $this->scopeConfig->getValue('trans_email/ident_general/name'),
            'email' => $this->scopeConfig->getValue('trans_email/ident_general/email'),
        ];

        $toEmail = $this->scopeConfig->getValue('dripconnect_general/log_settings/support_email');
        $toName = 'Drip Support';

        $subject = __('Logs from Magento server').' '.$this->getServerName();
        $emailTemplateVariables = [
            'subject' => $subject,
        ];

        $this->inlineTranslation->suspend();
        $emailTemplate = $this->transportBuilder
            ->setTemplateIdentifier('drip_connect_sendlogs_template')
            ->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
                'store' => $this->storeManager->getStore()->getId(),
            ])
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderData)
            ->addTo($toEmail, $toName);

        foreach ($dataToSend as $fileData) {
            $emailTemplate->addAttachment(
                file_get_contents($fileData['zip_path']),
                basename($fileData['zip_path'])
            );
        }

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    protected function getServerName()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }
}
