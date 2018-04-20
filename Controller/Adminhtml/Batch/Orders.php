<?php

namespace Drip\Connect\Controller\Adminhtml\Batch;

class Orders extends \Magento\Backend\App\Action
{
    /** @var @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    protected $salesResourceModelOrderCollectionFactory;

    /** @var \Drip\Connect\Helper\Order */
    protected $orderHelper;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesResourceModelOrderCollectionFactory,
        \Drip\Connect\Helper\Order $orderHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->salesResourceModelOrderCollectionFactory = $salesResourceModelOrderCollectionFactory;
        $this->orderHelper = $orderHelper;
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
            $collection = $this->salesResourceModelOrderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('state', array('nin' => array(
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_CLOSED
                    )))
                ->setPageSize(\Drip\Connect\Model\ApiCalls\Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batch = array();
            foreach ($collection as $order) {
                $data = $this->orderHelper->getOrderDataNew($order);
                $data['occurred_at'] = $order->getCreatedAt();
                $batch[] = $data;
            }

            $response = $this->orderHelper->proceedOrderBatch($batch, $accountId);

            if ($response->getResponseCode() != 202) { // drip success code for this action
                $result['success'] = 0;
                $result['message'] = $response->getErrorMessage();
                break;
            }
        } while ($page <= $collection->getLastPageNumber());

        return $resultJson->setData($result);
    }
}
