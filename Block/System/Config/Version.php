<?php

namespace Drip\Connect\Block\System\Config;

class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    /** @var \Magento\Framework\Module\ResourceInterface */
    private $moduleResource;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleResource = $moduleResource;
    }

    /**
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $version = $this->moduleResource->getDbVersion('Drip_Connect');

        $element->setData('value', "[Dummy]");

        $html = parent::render($element);
        $html = str_replace("[Dummy]", '<b>'.(string)$version."</b>", $html);

        return $html;
    }
}

