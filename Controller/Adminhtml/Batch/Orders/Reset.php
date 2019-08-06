<?php

namespace Drip\Connect\Controller\Adminhtml\Batch\Orders;

class Reset extends \Magento\Backend\App\Action
{
    /** @var @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->connectHelper = $connectHelper;
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

        $storeId = $this->getRequest()->getParam('store_id');
        $this->connectHelper->setOrdersSyncStateToStore($storeId, \Drip\Connect\Model\Source\SyncState::READY);

        return $resultJson->setData($result);
    }
}
