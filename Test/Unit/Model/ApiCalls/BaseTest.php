<?php

namespace Drip\Connect\Test\Unit\Model;

use PHPUnit\Framework\TestCase;

use \Drip\Connect\Logger\Logger;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Framework\ArchiveFactory;
use \Magento\Framework\Filesystem\DirectoryList;
use \Magento\Store\Model\StoreManagerInterface;
use \Drip\Connect\Model\Http\ClientFactory;
use \Drip\Connect\Model\Configuration;

use \Drip\Connect\Model\ApiCalls\Base;

/**
 * @covers \Drip\Connect\Model\ApiCalls\Base
 */
class BaseTest extends TestCase
{    
    /*
     * Since the WooBase is used to send data to Drip, we want to make sure 
     * this class wont cause issues in other part of the extension
     * before it is eliminated from the codebase.
     */ 
    public function testConstructor(){
        $baseClass = new Base(
        $this->createMock(Logger::class),
        $this->createMock(ScopeConfigInterface::class),
        $this->createMock(WriterInterface::class),
        $this->createMock(ArchiveFactory::class),
        $this->createMock(DirectoryList::class),
        $this->createMock(StoreManagerInterface::class),
        $this->createMock(ClientFactory::class),
        $this->createMock(Configuration::class),
        "some/endpoint",
        );

        $this->assertTrue(is_object($baseClass));
    }
}
