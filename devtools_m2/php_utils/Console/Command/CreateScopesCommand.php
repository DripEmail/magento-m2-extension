<?php

namespace Drip\TestUtils\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Utility command to create scopes in tests
 */
class CreateScopesCommand extends Command
{
    /** @var \Magento\Framework\App\State **/
    protected $state;

    /** @var \Magento\Store\Model\WebsiteFactory */
    protected $websiteFactory;

    /** @var \Magento\Store\Model\GroupFactory */
    protected $groupFactory;

    /** @var \Magento\Store\Model\StoreFactory */
    protected $storeFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManagerInterface;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Store\Model\GroupFactory $groupFactory,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct();

        $this->state = $state;
        $this->websiteFactory = $websiteFactory;
        $this->groupFactory = $groupFactory;
        $this->storeFactory = $storeFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('drip_testutils:createscopes')->setDescription('Create scopes using JSON from stdin');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Some bookkeeping
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

        $stdin = fopen('php://stdin', 'r');
        $data = stream_get_contents($stdin);
        $json = json_decode($data, true);

        if ($json === null) {
            throw new \UnexpectedValueException('Null JSON parse');
        }

        $defaultRootCategoryId = $this->storeManager->getStore()->getRootCategoryId();

        $website = $this->websiteFactory->create();
        $website->setName("site1_website")->setCode("site1_website");
        $website->save();

        $storeGroup = $this->groupFactory->create();
        $storeGroup->setName("site1_store")
                   ->setCode("site1_store")
                   ->setWebsiteId($website->getId())
                   ->setRootCategoryId($defaultRootCategoryId);
        $storeGroup->save();

        $storeView = $this->storeFactory->create();
        $storeView->setName("site1_store_view")
                  ->setCode("site1_store_view")
                  ->setGroupId($storeGroup->getId())
                  ->setWebsiteId($website->getId())
                  ->setIsActive(true);
        $storeView->save();
    }
}
