<?php
namespace Drip\Connect\Block\System\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Sendlogs extends \Drip\Connect\Block\System\Config\Button
{
    const BUTTON_TEMPLATE = 'system/config/sendlogs.phtml';

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('drip/support/sendlogs');
    }
}
