<?php
declare(strict_types=1);

namespace Shivas\VersioningBundle\Tests;

use Nyholm\BundleTest\TestKernel;
use Shivas\VersioningBundle\Formatter\FormatterInterface;
use Shivas\VersioningBundle\Formatter\GitDescribeFormatter;
use Shivas\VersioningBundle\Provider\GitRepositoryProvider;
use Shivas\VersioningBundle\Provider\InitialVersionProvider;
use Shivas\VersioningBundle\Provider\RevisionProvider;
use Shivas\VersioningBundle\Provider\VersionProvider;
use Shivas\VersioningBundle\Service\VersionManager;
use Shivas\VersioningBundle\Service\VersionManagerInterface;
use Shivas\VersioningBundle\ShivasVersioningBundle;
use Shivas\VersioningBundle\Twig\VersionExtension;
use Shivas\VersioningBundle\Writer\VersionWriter;
use Shivas\VersioningBundle\Writer\WriterInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @covers \Shivas\VersioningBundle\ShivasVersioningBundle
 */
class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(ShivasVersioningBundle::class);
        $kernel->addTestCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                // Service would be removed because it's unused.
                $container->findDefinition('shivas_versioning.twig.version')->setPublic(true);
            }
        });
        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testInitBundle()
    {
        $this->bootKernel();
        $container = $this->getContainer();

        $this->assertTrue($container->has('shivas_versioning.twig.version'));
        $this->assertInstanceOf(VersionExtension::class, $container->get('shivas_versioning.twig.version'));

        $this->assertTrue($container->has(FormatterInterface::class));
        $this->assertInstanceOf(GitDescribeFormatter::class, $container->get(FormatterInterface::class));

        $this->assertTrue($container->has(WriterInterface::class));
        $this->assertInstanceOf(VersionWriter::class, $container->get(WriterInterface::class));

        $this->assertTrue($container->has(VersionManagerInterface::class));
        $versionManager = $container->get(VersionManagerInterface::class);
        $this->assertInstanceOf(VersionManager::class, $versionManager);

        $expectedProviders = [
            'version' => [
                'providerClass' => VersionProvider::class,
                'priority' => 100,
            ],
            'git' => [
                'providerClass' => GitRepositoryProvider::class,
                'priority' => -25,
            ],
            'revision' => [
                'providerClass' => RevisionProvider::class,
                'priority' => -50,
            ],
            'init' => [
                'providerClass' => InitialVersionProvider::class,
                'priority' => -75,
            ],
        ];
        $providers = $versionManager->getProviders();
        $this->assertSame(array_keys($expectedProviders), array_keys($providers));

        foreach ($expectedProviders as $alias => $definition) {
            $entry = $providers[$alias];
            $this->assertSame($definition['priority'], $entry['priority']);
            $this->assertInstanceOf($definition['providerClass'], $entry['provider']);
            $this->assertSame($alias, $entry['alias']);
        }
    }
}
