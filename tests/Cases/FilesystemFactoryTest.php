<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfTest\Filesystem\Cases;

use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use Hyperf\Filesystem\Adapter\LocalAdapterFactory;
use Hyperf\Filesystem\Adapter\MemoryAdapterFactory;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Filesystem\FilesystemInvoker;
use Hyperf\Filesystem\Version;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Reflection\ClassInvoker;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Memory\MemoryAdapter;

! defined('BASE_PATH') && define('BASE_PATH', '.');

/**
 * @internal
 * @coversNothing
 */
class FilesystemFactoryTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        $container = new Container(new DefinitionSource([]));
        ApplicationContext::setContainer($container);
    }

    public function testGet()
    {
        $config = new Config([
            'file' => [
                'default' => 'local',
                'storage' => [
                    'local' => [
                        'driver' => LocalAdapterFactory::class,
                    ],
                    'test' => [
                        'driver' => MemoryAdapterFactory::class,
                    ],
                ],
            ],
        ]);
        $container = ApplicationContext::getContainer();
        $container->set(ConfigInterface::class, $config);
        $factory = new FilesystemFactory($container);
        $this->assertInstanceOf(\League\Flysystem\Filesystem::class, $fileSystem = $factory->get('test'));
        if (Version::isV2()) {
            $invoker = new ClassInvoker($fileSystem);
            $this->assertInstanceOf(InMemoryFilesystemAdapter::class, $invoker->adapter);
        } else {
            $this->assertInstanceOf(MemoryAdapter::class, $fileSystem->getAdapter());
        }
    }

    public function testDefault()
    {
        $config = new Config([
            'file' => [
                'default' => 'local',
                'storage' => [
                    'local' => [
                        'driver' => LocalAdapterFactory::class,
                        'root' => '.',
                    ],
                    'test' => [
                        'driver' => MemoryAdapterFactory::class,
                    ],
                ],
            ],
        ]);
        $container = ApplicationContext::getContainer();
        $container->set(ConfigInterface::class, $config);
        $container->define(Filesystem::class, FilesystemInvoker::class);
        $fileSystem = $container->get(Filesystem::class);
        $this->assertInstanceOf(\League\Flysystem\Filesystem::class, $fileSystem);
        if (Version::isV2()) {
            $invoker = new ClassInvoker($fileSystem);
            $this->assertInstanceOf(LocalFilesystemAdapter::class, $invoker->adapter);
        } else {
            $this->assertInstanceOf(Local::class, $fileSystem->getAdapter());
        }
    }

    public function testMissingConfiguration()
    {
        $config = new Config([]);
        $container = ApplicationContext::getContainer();
        $container->set(ConfigInterface::class, $config);
        $container->define(Filesystem::class, FilesystemInvoker::class);
        $fileSystem = $container->get(Filesystem::class);
        $this->assertInstanceOf(\League\Flysystem\Filesystem::class, $fileSystem);
        if (Version::isV2()) {
            $invoker = new ClassInvoker($fileSystem);
            $this->assertInstanceOf(LocalFilesystemAdapter::class, $invoker->adapter);
        } else {
            $this->assertInstanceOf(Local::class, $fileSystem->getAdapter());
        }
    }
}
