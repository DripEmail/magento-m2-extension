<?php

namespace Drip\Connect\Controller\Adminhtml\Batch;

class Customers extends \Magento\Backend\App\Action
{
    /** @var @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory */
    protected $customerResourceModelCustomerCollectionFactory;

    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerResourceModelCustomerCollectionFactory,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerResourceModelCustomerCollectionFactory = $customerResourceModelCustomerCollectionFactory;
        $this->customerHelper = $customerHelper;
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

        $accountId = $this->getRequest()->getParam('account_id');

        $page = 1;
        do {
            $collection = $this->customerResourceModelCustomerCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batch = array();
            foreach ($collection as $customer) {
                $data = $this->customerHelper->prepareCustomerData($customer);
                $data['tags'] = array('Synced from Magento');
                $batch[] = $data;
            }

            $response = $this->customerHelper->proceedAccountBatch($batch, $accountId);

            if ($response->getResponseCode() != 201) { // drip success code for this action
                $result['success'] = 0;
                $result['message'] = $response->getErrorMessage();
                break;
            }
        } while ($page <= $collection->getLastPageNumber());

        return $resultJson->setData($result);
    }
}
