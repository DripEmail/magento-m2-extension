<?php

namespace Drip\Connect\Controller\Adminhtml\Batch;

class Customers extends \Magento\Backend\App\Action
{
    /** @var @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();                       
        $result = [
            'success' => 1,
            'message' => ''
        ]; 

        $formKeyIsValid = $this->_formKeyValidator->validate($this->getRequest());
        $isPost = $this->getRequest()->isPost();
        $isAjax = $this->getRequest()->isAjax();
        if (!$formKeyIsValid || !$isPost || !$isAjax) {
            $result['success'] = 0;
        }

        // todo: get all customers, create batch, send it to Batch Api

        return $resultJson->setData($result);
    }
}
