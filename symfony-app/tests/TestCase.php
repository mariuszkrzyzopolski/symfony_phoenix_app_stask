<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class TestCase extends KernelTestCase
{
    protected static ?ContainerInterface $testContainer = null;
    private static bool $databaseInitialized = false;
    private static bool $schemaInitialized = false;

    protected function setUp(): void
    {
        self::bootKernel();
        self::$testContainer = static::getContainer();
        
        self::initializeTestDatabase();
        self::initializeTestSchema();
    }

    protected function tearDown(): void
    {
        self::$testContainer = null;
    }

    public static function tearDownAfterClass(): void
    {
        self::dropTestDatabase();
        parent::tearDownAfterClass();
    }

    protected static function getTestContainer(): ?ContainerInterface
    {
        return self::$testContainer;
    }

    private static function initializeTestDatabase(): void
    {
        if (self::$databaseInitialized) {
            return;
        }

        $container = static::getTestContainer();
        if (!$container) {
            return;
        }

        $connection = $container->get('doctrine.dbal.default_connection');
        $databaseName = $connection->getDatabase();
        
        // Create database if it doesn't exist
        try {
            $connection->executeStatement("CREATE DATABASE IF NOT EXISTS $databaseName");
            self::$databaseInitialized = true;
        } catch (\Exception $e) {
            // Database might already exist
            self::$databaseInitialized = true;
        }
    }

    private static function initializeTestSchema(): void
    {
        if (self::$schemaInitialized) {
            return;
        }

        $container = static::getTestContainer();
        if (!$container) {
            return;
        }

        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application(self::$kernel);
        $application->setAutoExit(false);
        
        $input = new \Symfony\Component\Console\Input\ArrayInput(['command' => 'doctrine:schema:create', '--env=test']);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        
        try {
            $exitCode = $application->run($input, $output);
            self::$schemaInitialized = true;
        } catch (\Exception $e) {
            // Schema creation might fail if already exists
            self::$schemaInitialized = true;
        }
    }

    private static function dropTestDatabase(): void
    {
        $container = static::getTestContainer();
        if (!$container) {
            return;
        }

        $connection = $container->get('doctrine.dbal.default_connection');
        $databaseName = $connection->getDatabase();
        
        if (str_contains($databaseName, 'test')) {
            try {
                $connection->executeStatement("DROP SCHEMA IF EXISTS $databaseName CASCADE");
                $connection->executeStatement("DROP DATABASE IF EXISTS $databaseName");
            } catch (\Exception $e) {
            }
        }
    }
}
