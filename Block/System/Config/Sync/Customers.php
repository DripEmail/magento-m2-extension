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
        return 'TODO';
    }
}
