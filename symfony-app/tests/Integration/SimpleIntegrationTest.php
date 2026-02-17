<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SimpleIntegrationTest extends KernelTestCase
{
    private ?ContainerInterface $container = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        self::bootKernel();
        $this->container = static::getContainer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->container = null;
    }

    public function testContainerWorks(): void
    {
        $this->assertNotNull($this->container);
        $this->assertTrue($this->container->has('doctrine'));
    }
    
    public function testBasicRequest(): void
    {
        // This test will be moved to application tests
        // For now, just test that kernel boots correctly
        $this->assertTrue(true);
    }
}
