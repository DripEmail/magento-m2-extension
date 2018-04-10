<?php
namespace Drip\Connect\Block\View\Element;

class Template extends \Magento\Framework\View\Element\Template
{
    public function getConfig()
    {
        return $this->_scopeConfig;
    }
}
