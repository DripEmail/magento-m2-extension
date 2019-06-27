<?php

namespace Drip\Connect\Model\Mail;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @param string $fileContent
     * @param string $fileName
     *
     * @return this
     */
    public function addAttachment($fileContent, $fileName)
    {
        $this->message->createAttachment(
            $fileContent,
            \Zend_Mime::TYPE_OCTETSTREAM,
            \Zend_Mime::DISPOSITION_ATTACHMENT,
            \Zend_Mime::ENCODING_BASE64,
            $fileName
        );

        return $this;
    }
}

