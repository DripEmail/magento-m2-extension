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
        \Drip\Connect\Model\ApiCalls\Helper\Batches\EventsFactory $connectApiCallsHelperBatchesEventsFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerResourceModelCustomerCollectionFactory = $customerResourceModelCustomerCollectionFactory;
        $this->customerHelper = $customerHelper;
        $this->connectApiCallsHelperBatchesEventsFactory = $connectApiCallsHelperBatchesEventsFactory;
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

            $batchCustomer = array();
            $batchEvents = array();
            foreach ($collection as $customer) {
                $dataCustomer = $this->customerHelper->prepareCustomerData($customer);
                $dataCustomer['tags'] = array('Synced from Magento');
                $batchCustomer[] = $dataCustomer;

                $dataEvents = array(
                    'email' => $customer->getEmail(),
                    'action' => ($customer->getDrip()
                        ? \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_UPDATED
                        : \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_NEW),
                );
                $batchEvents[] = $dataEvents;

                if (!$customer->getDrip()) {
                    $customer->setNeedToUpdateAttribute(1);
                    $customer->setDrip(1);
                }
            }

            $response = $this->customerHelper->proceedAccountBatch($batchCustomer, $accountId);

            if ($response->getResponseCode() != 201) { // drip success code for this action
                $result['success'] = 0;
                $result['message'] = $response->getErrorMessage();
                break;
            }

            $response = $this->connectApiCallsHelperBatchesEventsFactory->create([
                'data' => [
                    'batch' => $batchEvents,
                    'account' => $accountId,
                ]
            ])->call();

            if ($response->getResponseCode() != 201) { // drip success code for this action
                $result['success'] = 0;
                $result['message'] = $response->getErrorMessage();
                break;
            }

            foreach ($collection as $customer) {
                if ($customer->getNeedToUpdateAttribute()) {
                    $customer->getResource()->saveAttribute($customer, 'drip');
                }
            }
        } while ($page <= $collection->getLastPageNumber());

        return $resultJson->setData($result);
    }
}
