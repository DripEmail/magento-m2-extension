<?php
namespace Drip\Connect\Block\System\Config\Sync;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Customers extends \Drip\Connect\Block\System\Config\Sync\Button
{
    const BUTTON_TEMPLATE = 'system/config/sync/customers.phtml';

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('drip/batch/customers');
    }

    public function isSyncAvailable()
    {
        if (!$this->isModuleActive()) {
            return false;
        }
        if ($this->connectHelper->getCustomersSyncStateForStore($this->_request->getParam('store')) != \Drip\Connect\Model\Source\SyncState::READY) {
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getStateLabel()
    {
        return \Drip\Connect\Model\Source\SyncState::getLabel(
            $this->_scopeConfig->getValue(
                'dripconnect_general/actions/sync_customers_data_state',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->_request->getParam('store')
            )
        );
    }
}
