<?php

namespace Drip\Connect\Controller\Adminhtml\Batch;

class Orders extends \Magento\Backend\App\Action
{
    /** @var @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configFactory = $configFactory;
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
        $config = $this->configFactory->create($storeId);
        $config->setOrdersSyncState(\Drip\Connect\Model\Source\SyncState::QUEUED);

        return $resultJson->setData($result);
    }
}
