<?php

/**
 * Copyright 2015-2016, SellerLabs <scope-devs@sellerlabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the SellerLabs package
 */

namespace Tests\SellerLabs\Mws;

use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use SellerLabs\Injected\InjectedTrait;
use SellerLabs\Mws\MwsException;
use SellerLabs\Mws\Mws;


/**
 * Class MwsTest.
 *
 * @property MockInterface config
 *
 * @author Dennis S. Hennen <dennis@roundsphere.com>
 * @package Tests\SellerLabs\Scope\Support
 */
class MwsTest extends TestCase
{
    use InjectedTrait;

    /** @var string */
    protected $className = Mws::class;

    /** @var Mws */
    private $mws;

    public function setUp()
    {
        parent::setUp();
        $this->mws = $this->make();
        $this->config->shouldReceive('get')
            ->with('mws.aws_key')
            ->andReturn('aws_key')
            ->once();
        $this->config->shouldReceive('get')
            ->with('mws.aws_secret')
            ->andReturn('aws_secret')
            ->once();
        $this->config->shouldReceive('get')
            ->with('mws.seller_id')
            ->andReturn('seller_id')
            ->once();
        $this->config->shouldReceive('get')
            ->with('mws.mock', false)
            ->andReturn(true)
            ->once();
    }

    public function testGetMyFeesEstimateSuccess()
    {
        // The AWS mock returns 'String' as the status. Match to allow success.
        $this->config->shouldReceive('get')
            ->with('mws.success_string', 'Success')
            ->andReturn('String')
            ->once();
        $actual = $this->mws->getMyFeesEstimate('B00XTCERLE', 10);
        $expected = [
            'total' => 100,
            'detail' => [
                'String' => 100,
            ],
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testGetMyFeesEstimateFailure()
    {
        // The AWS mock returns 'String' as the status. Force a failure.
        $this->config->shouldReceive('get')
            ->with('mws.success_string', 'Success')
            ->andReturn('Success')
            ->once();
        $this->expectException(MwsException::class);
        $this->mws->getMyFeesEstimate('B00XTCERLE', 10);
    }

    public function testGetMatchingProductForIdSuccess()
    {
        // The AWS mock returns 'String' as the status. Match to allow success.
        $this->config->shouldReceive('get')
            ->with('mws.success_string', 'Success')
            ->andReturn('String')
            ->once();
        $actual = $this->mws->getMatchingProductForId('B00XTCERLE');
    }

    public function testGetMatchingProductForIdFailure()
    {
        // The AWS mock returns 'String' as the status. Force a failure.
        $this->config->shouldReceive('get')
            ->with('mws.success_string', 'Success')
            ->andReturn('Success')
            ->once();
        $this->expectException(MwsException::class);
        $this->mws->getMatchingProductForId('B00XTCERLE');
    }
}
