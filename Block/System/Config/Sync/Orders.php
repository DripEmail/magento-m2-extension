<?php
namespace Drip\Connect\Block\System\Config\Sync;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Orders extends \Drip\Connect\Block\System\Config\Sync\Button
{
    const BUTTON_TEMPLATE = 'system/config/sync/orders.phtml';

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
