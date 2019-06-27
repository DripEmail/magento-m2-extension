<?php

namespace Drip\Connect\Controller\Adminhtml\Support;

class Sendlogs extends \Magento\Backend\App\Action
{
    /** @var @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Drip\Connect\Helper\Logs*/
    protected $logsHelper;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Drip\Connect\Helper\Logs $logsHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logsHelper = $logsHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = [
            'status' => \Drip\Connect\Helper\Logs::SENDLOGS_RESPONSE_OK,
            'message' => __('Logs have been sent'),
        ];

        $formKeyIsValid = $this->_formKeyValidator->validate($this->getRequest());
        $isPost = $this->getRequest()->isPost();
        $isAjax = $this->getRequest()->isAjax();
        if (!$formKeyIsValid || !$isPost || !$isAjax) {
            $result['status'] = \Drip\Connect\Helper\Logs::SENDLOGS_RESPONSE_FAIL;
            $result['message'] = __('Invalid ajax call');
        } else {
            $storeId = $this->getRequest()->getParam('store_id');
            try {
                $this->logsHelper->sendLogs($storeId);
            } catch (\Exception $e) {
                $result['status'] = \Drip\Connect\Helper\Logs::SENDLOGS_RESPONSE_FAIL;
                $result['message'] = $e->getMessage();
            }
        }

        return $resultJson->setData($result);
    }
}
