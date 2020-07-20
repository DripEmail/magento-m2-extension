<?php

namespace Drip\Connect\Observer;

abstract class Base implements \Magento\Framework\Event\ObserverInterface
{
    const REGISTRY_KEY_ORDER_ITEMS_OLD_DATA = 'oldorderitemsdata';
    const REGISTRY_KEY_ORDER_OLD_DATA = 'oldorderdata';

    /** @var \Drip\Connect\Model\ConfigurationFactory */
    protected $configFactory;

    /** @var \Drip\Connect\Logger\Logger */
    protected $logger;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Drip\Connect\Logger\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->configFactory = $configFactory;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    abstract protected function executeWhenEnabled(\Magento\Framework\Event\Observer $observer);

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isActive($observer)) {
            return;
        }

        $myClass = get_class($this);
        $this->logger->info("Observer triggered: {$myClass}");

        try {
            $this->executeWhenEnabled($observer);
        } catch (\Exception $e) {
            // We should never blow up a customer's site due to bugs in our code.
            $this->logger->critical($e);
        }
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool
     */
    protected function isActive(\Magento\Framework\Event\Observer $observer)
    {
        $websites = $this->storeManager->getWebsites();

        foreach ($websites as $website) {
          $config = $this->configFactory->createFromWebsiteId($website->getId());

          if ($config->getIntegrationToken() !== null) {
            $active = true;
          }
        }
        return $active;
    }
}
