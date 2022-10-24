<?php

namespace Drip\Connect\Test\Unit\Model\ApiCalls;

use Drip\Connect\Model\ApiCalls\Base;

use Drip\Connect\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\ArchiveFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Drip\Connect\Model\Http\ClientFactory;
use Drip\Connect\Model\Configuration;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Drip\Connect\Model\ApiCalls\Base
 */
class BaseTest extends TestCase
{
    /** 
     * @var Logger|MockObject
    */
    private $loggerMock;

    /** 
     * @var ScopeConfigInterface|MockObject
    */
    private $scopeConfMock;

    /** 
     * @var WriterInterface|MockObject
    */
    private $writerIntMock;

    /** 
     * @var ArchiveFactory|MockObject
    */
    private $archiveFacMock;

    /** 
     * @var DirectoryList|MockObject
    */
    private $directoryListMock;

    /** 
     * @var StoreManagerInterface|MockObject
    */
    private $storeManMock;

    /** 
     * @var Client|MockObject
    */
    private $clientFactory;

    /** 
     * @var Configuration
    */
    private $configuration;

    protected function setup(): void
    {
        $this->loggerMock = $this->getMockBuilder(Logger::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->scopeConfMock = $this->getMockBuilder(ScopeConfigInterface::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->writerIntMock = $this->getMockBuilder(WriterInterface::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->archiveFacMock = $this->getMockBuilder(ArchiveFactory::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->storeManMock = $this->getMockBuilder(StoreManagerInterface::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->clientFactMock = $this->getMockBuilder(ClientFactory::class)
        ->setMethods(['create', 'setHeaders', 'setAuth'])
        ->disableOriginalConstructor()
        ->getMock();


        $this->configuration = new Configuration(
            $this->writerIntMock,
            $this->scopeConfMock,
            $this->storeManMock,
            100
        );
    }
    public function testMissingFunctions()
    {
        $base = new Base(
            $this->loggerMock,
            $this->scopeConfMock,
            $this->writerIntMock,
            $this->archiveFacMock,
            $this->directoryListMock,
            $this->storeManMock,
            $this->clientFactMock,
            $this->configuration,
            "products",
            false
        );
    }
}