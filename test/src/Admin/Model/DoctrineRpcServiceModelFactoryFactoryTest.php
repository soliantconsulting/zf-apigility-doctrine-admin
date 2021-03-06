<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Admin\Model;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use ZF\Apigility\Admin\Model\ModuleModel;
use ZF\Apigility\Admin\Model\ModulePathSpec;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceModelFactory;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceModelFactoryFactory;
use ZF\Configuration\ConfigResourceFactory;
use ZF\Configuration\ResourceFactory;

class DoctrineRpcServiceModelFactoryFactoryTest extends TestCase
{
    /**
     * @var ProphecyInterface|ContainerInterface
     */
    private $container;

    protected function setUp()
    {
        parent::setUp();

        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function missingDependencies()
    {
        return [
            'all' => [[
                ModulePathSpec::class => false,
                ConfigResourceFactory::class => false,
                ModuleModel::class => false,
                'SharedEventManager' => false,
            ]],
            'ModulePathSpec' => [[
                ModulePathSpec::class => false,
                ConfigResourceFactory::class => true,
                ModuleModel::class => true,
                'SharedEventManager' => true,
            ]],
            'ConfigResourceFactory' => [[
                ModulePathSpec::class => true,
                ConfigResourceFactory::class => false,
                ModuleModel::class => true,
                'SharedEventManager' => true,
            ]],
            'ModuleModel' => [[
                ModulePathSpec::class => true,
                ConfigResourceFactory::class => true,
                ModuleModel::class => false,
                'SharedEventManager' => true,
            ]],
            'SharedEventManager' => [[
                ModulePathSpec::class => true,
                ConfigResourceFactory::class => true,
                ModuleModel::class => true,
                'SharedEventManager' => false,
            ]],
        ];
    }

    /**
     * @dataProvider missingDependencies
     *
     * @var array $dependencies
     */
    public function testFactoryRaisesExceptionIfDependenciesAreMissing($dependencies)
    {
        $factory = new DoctrineRpcServiceModelFactoryFactory();

        foreach ($dependencies as $dependency => $presence) {
            $this->container->has($dependency)->willReturn($presence);
        }

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('missing one or more dependencies');
        $factory($this->container->reveal());
    }

    public function testFactoryReturnsConfiguredDoctrineRpcServiceModelFactory()
    {
        $factory               = new DoctrineRpcServiceModelFactoryFactory();
        $pathSpec              = $this->prophesize(ModulePathSpec::class)->reveal();
        $configResourceFactory = $this->prophesize(ResourceFactory::class)->reveal();
        $sharedEvents          = $this->prophesize(SharedEventManagerInterface::class)->reveal();
        $moduleModel           = $this->prophesize(ModuleModel::class)->reveal();

        $this->container->has(ModulePathSpec::class)->willReturn(true);
        $this->container->has(ConfigResourceFactory::class)->willReturn(true);
        $this->container->has(ModuleModel::class)->willReturn(true);
        $this->container->has('SharedEventManager')->willReturn(true);

        $this->container->get(ModulePathSpec::class)->willReturn($pathSpec);
        $this->container->get(ConfigResourceFactory::class)->willReturn($configResourceFactory);
        $this->container->get(ModuleModel::class)->willReturn($moduleModel);
        $this->container->get('SharedEventManager')->willReturn($sharedEvents);

        $rpcFactory = $factory($this->container->reveal());

        $this->assertInstanceOf(DoctrineRpcServiceModelFactory::class, $rpcFactory);
        $this->assertAttributeSame($pathSpec, 'modules', $rpcFactory);
        $this->assertAttributeSame($configResourceFactory, 'configFactory', $rpcFactory);
        $this->assertAttributeSame($moduleModel, 'moduleModel', $rpcFactory);
        $this->assertAttributeSame($sharedEvents, 'sharedEventManager', $rpcFactory);
    }
}
