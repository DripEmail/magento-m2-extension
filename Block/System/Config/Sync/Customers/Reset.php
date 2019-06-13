<?php
namespace Drip\Connect\Block\System\Config\Sync\Customers;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Reset extends \Drip\Connect\Block\System\Config\Sync\Button
{
    const BUTTON_TEMPLATE = 'system/config/sync/customers/reset.phtml';

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('drip/batch_customers/reset');
    }
}
