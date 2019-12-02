<?php
namespace Drip\Connect\Block\System\Config\Sync;

use Magento\Framework\App\Config\ScopeConfigInterface;

abstract class Button extends \Magento\Config\Block\System\Config\Form\Field
{
    const BUTTON_TEMPLATE = '';

    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Helper\Data $connectHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configFactory = $configFactory;
        $this->connectHelper = $connectHelper;
    }

    /**
     * check if module active
     */
    public function isModuleActive()
    {
        return $this->configFactory->createForCurrentStoreParam()->isEnabled();
    }

    /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }

    /**
     * Render button
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    abstract public function getAjaxUrl();

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $config = $this->configFactory->create((int) $this->_request->getParam('store'));
        $this->addData(
            [
                'html_id' => $element->getHtmlId(),
                'button_label' => __($originalData['button_label']),
                'store_id' => $config->getStoreId(),
                'account_id' => $config->getAccountId(),
            ]
        );
        return $this->_toHtml();
    }
}
