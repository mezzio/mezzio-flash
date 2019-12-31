<?php

/**
 * @see       https://github.com/mezzio/mezzio-flash for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-flash/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-flash/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Flash;

use Mezzio\Flash\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function setUp()
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvocationReturnsArray()
    {
        $config = ($this->provider)();
        $this->assertInternalType('array', $config);
        return $config;
    }

    /**
     * @depends testInvocationReturnsArray
     */
    public function testReturnedArrayContainsDependencies(array $config)
    {
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertInternalType('array', $config['dependencies']);
    }
}
