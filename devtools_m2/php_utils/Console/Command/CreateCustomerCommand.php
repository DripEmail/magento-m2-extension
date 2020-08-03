<?php

namespace Drip\TestUtils\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create customer for tests
 */
class CreateCustomerCommand extends Command
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ) {
        parent::__construct();

        $this->customerFactory = $customerFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('drip_testutils:createcustomer')->setDescription('Create customer using JSON from stdin');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Some bookkeeping
        // $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

        $stdin = fopen('php://stdin', 'r');
        $data = stream_get_contents($stdin);
        $json = json_decode($data, true);

        if ($json === null) {
            throw new \UnexpectedValueException('Null JSON parse');
        }

        $defaults = [
            // 'websiteId' => 1,
            // 'store' => 1,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'jd1@example.com',
            'password' => 'somepassword',
        ];
        $fullData = array_replace_recursive($defaults, $json);
        $customer = $this->customerFactory->create();
        // This assumes that you properly name all of the attributes. But we control both ends, so it should be fine.
        foreach ($fullData as $key => $value) {
            $methodName = "set".ucfirst($key);
            $customer->$methodName($value);
        }
        $customer->save();
    }
}
