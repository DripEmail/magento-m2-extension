<?php

namespace Drip\TestUtils\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateProductCommand extends Command
{
    /** @var \Magento\Framework\App\State **/
    protected $state;

    /** @var \Drip\TestUtils\Creators\SimpleProductCreatorFactory **/
    protected $simpleProductCreatorFactory;

    /** @var \Drip\TestUtils\Creators\ConfigurableProductCreatorFactory **/
    protected $configurableProductCreatorFactory;

    /** @var \Drip\TestUtils\Creators\GroupedProductCreatorFactory **/
    protected $groupedProductCreatorFactory;

    /** @var \Drip\TestUtils\Creators\BundleProductCreatorFactory **/
    protected $bundleProductCreatorFactory;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Drip\TestUtils\Creators\SimpleProductCreatorFactory $simpleProductCreatorFactory,
        \Drip\TestUtils\Creators\ConfigurableProductCreatorFactory $configurableProductCreatorFactory,
        \Drip\TestUtils\Creators\GroupedProductCreatorFactory $groupedProductCreatorFactory,
        \Drip\TestUtils\Creators\BundleProductCreatorFactory $bundleProductCreatorFactory
    ) {
        parent::__construct();

        $this->state = $state;
        $this->simpleProductCreatorFactory = $simpleProductCreatorFactory;
        $this->configurableProductCreatorFactory = $configurableProductCreatorFactory;
        $this->groupedProductCreatorFactory = $groupedProductCreatorFactory;
        $this->bundleProductCreatorFactory = $bundleProductCreatorFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('drip_testutils:createproduct')->setDescription('Create product using JSON from stdin');
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
            throw new \Exception('Null JSON parse');
        }

        $type = array_key_exists('typeId', $json) ? $json['typeId'] : '';
        $factory = null;
        switch ($type) {
            case 'simple':
            case '':
            case null:
                $factory = $this->simpleProductCreatorFactory;
                break;
            case 'configurable':
                $factory = $this->configurableProductCreatorFactory;
                break;
            case 'grouped':
                $factory = $this->groupedProductCreatorFactory;
                break;
            case 'bundle':
                $factory = $this->bundleProductCreatorFactory;
                break;
            default:
                throw new \Exception("Unsupported type: ${type}");
        }

        $factory->create(['productData' => $json])->create();
    }
}
