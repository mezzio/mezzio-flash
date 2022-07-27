<?php

declare(strict_types=1);

namespace MezzioTest\Flash;

use Mezzio\Flash\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $provider;

    public function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    /** @return array<string, mixed> */
    public function testInvocationReturnsArray(): array
    {
        $config = $this->provider->__invoke();
        $this->assertNotEmpty($config);

        return $config;
    }

    /**
     * @depends testInvocationReturnsArray
     * @param array<string, mixed> $config
     */
    public function testReturnedArrayContainsDependencies(array $config): void
    {
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertIsArray($config['dependencies']);
    }
}
