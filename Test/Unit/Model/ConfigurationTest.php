<?php

namespace Drip\Connect\Test\Unit\Model;

use PHPUnit\Framework\TestCase;

use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\StoreManagerInterface;

use Drip\Connect\Model\Configuration;

/**
 * @covers \Drip\Connect\Model\Configuration
 */
class ConfigurationTest extends TestCase
{
    
    /**
     *  @var Configuration Test constructor and other methods without protected methods invoked
    */
    private $configuration;

    /**
     *  @var Configuration Test methods that invoke protected method
    */
    private $configurationMock;

    protected function setup(): void
    {
        $this->config = new Configuration(
            $this->createMock(WriterInterface::class),
            $this->createMock(ScopeConfigInterface::class),
            $this->createMock(StoreManagerInterface::class),
            100
        );
        

        $this->configMock = $this->getMockBuilder(Configuration::class)
        ->disableOriginalConstructor()
        ->getMock();
    }

    public function testGetWebsiteId()
    {
        $this->assertEquals(100, $this->config->getWebsiteId());
    }

    public function testGetTimeOut()
    {
        $this->assertEquals(30000, $this->config->getTimeOut());
    }

    public function testGetAccountParam()
    {
        $this->configMock->expects($this->once())
        ->method('setAccountParam')
        ->with('12345');
        $this->configMock->expects($this->once())
        ->method('getAccountParam')
        ->willReturn('12345');

        $this->configMock->setAccountParam(12345);
        $this->assertEquals('12345', $this->configMock->getAccountParam());
    }


    public function testGetWisUrl()
    {
        $this->configMock->expects($this->once())
        ->method('getWisUrl')
        ->willReturn($this->config::WIS_URL_PATH);

        $this->configMock->getWisUrl();
    }
}
