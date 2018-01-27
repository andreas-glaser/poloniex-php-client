<?php

namespace AndreasGlaser\PPC\Tests;

use AndreasGlaser\PPC\PPC;

/**
 * Class PPCTest
 *
 * @package AndreasGlaser\PPC\Tests
 */
class PPCTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PPC
     */
    protected $ppc;

    protected function setUp()
    {
        parent::setUp();
        $this->ppc = new PPC();
    }

    /**
     */
    public function testConstructor()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Both "apiKey" and "apiSecret" have to be provided');

        new PPC(3);
    }

    public function testGetTicker()
    {
        $result = $this->ppc->getTicker();

        $this->assertTrue(is_array($result->decoded));

        foreach ($result->decoded AS $record) {
            $this->assertArrayHasKey('id', $record);
            $this->assertArrayHasKey('last', $record);
            $this->assertArrayHasKey('lowestAsk', $record);
            $this->assertArrayHasKey('highestBid', $record);
            $this->assertArrayHasKey('percentChange', $record);
            $this->assertArrayHasKey('baseVolume', $record);
            $this->assertArrayHasKey('quoteVolume', $record);
            $this->assertArrayHasKey('isFrozen', $record);
            $this->assertArrayHasKey('high24hr', $record);
            $this->assertArrayHasKey('low24hr', $record);
        }
    }

    public function testGet24hVolume()
    {
        $result = $this->ppc->get24hVolume();
        $this->assertTrue(is_array($result->decoded));
    }

    public function testGetOrderBook()
    {
        $result = $this->ppc->getOrderBook('BTC_ETH');
        $this->assertTrue(is_array($result->decoded));
    }

    public function testGetTradeHistory()
    {
        $result = $this->ppc->getPublicTradeHistory('BTC_ETH', time() - 20, time());
        $this->assertTrue(is_array($result->decoded));
    }

    public function testGetChartData()
    {
        $result = $this->ppc->getChartData('BTC_ETH', time() - (60 * 60 * 12), time(), 900);
        $this->assertTrue(is_array($result->decoded));
    }

    public function testGetCurrencies()
    {
        $result = $this->ppc->getCurrencies();
        $this->assertTrue(is_array($result->decoded));
    }

    public function testGetLoanOrders()
    {
        $result = $this->ppc->getLoanOrders('BTC');
        $this->assertTrue(is_array($result->decoded));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trading request are not possible if api key and secret have not been set
     */
    public function testTradingException()
    {
        (new PPC())->getBalances();
    }

    /**
     * @expectedException \GuzzleHttp\Exception\ClientException
     */
    public function testTradingExceptionWithApiKey()
    {
        (new PPC('dummy', 'dummy'))->getBalances();
    }
}