<?php
declare(strict_types=1);

namespace TheCodingMachine\Yaco\ServiceProvider;


use Puli\Discovery\Api\Discovery;
use Puli\Discovery\Binding\ClassBinding;
use TheCodingMachine\Yaco\Compiler;
use TheCodingMachine\Yaco\Definition\AliasDefinition;
use TheCodingMachine\Yaco\Definition\FactoryCallDefinition;
use TheCodingMachine\Yaco\Definition\Reference;

class ServiceProviderLoader
{
    /**
     * @var Compiler
     */
    private $compiler;

    /**
     * @param Compiler $compiler
     */
    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Discovers service provider class names using Puli.
     *
     * @param Discovery $discovery
     * @return string[] Returns an array of service providers.
     */
    public function discover(Discovery $discovery) : array {
        $bindings = $discovery->findBindings('container-interop/service-provider');
        $serviceProviders = [];

        foreach ($bindings as $binding) {
            if ($binding instanceof ClassBinding) {
                $serviceProviders[] = $binding->getClassName();
            }
        }
        return $serviceProviders;
    }

    /**
     * Discovers and loads the service providers using Puli.
     *
     * @param Discovery $discovery
     */
    public function discoverAndLoad(Discovery $discovery) {
        $serviceProviders = $this->discover($discovery);

        foreach ($serviceProviders as $serviceProvider) {
            $this->load($serviceProvider);
        }
    }

    public function load(string $serviceProviderClassName)
    {
        if (!class_exists($serviceProviderClassName)) {
            throw new InvalidArgumentException(sprintf('ServiceProviderLoader::load expects a valid class name. Could not find class "%s"', $serviceProviderClassName));
        }

        $serviceFactories = call_user_func([$serviceProviderClassName, 'getServices']);

        foreach ($serviceFactories as $serviceName => $methodName) {
            $this->registerService($serviceName, $serviceProviderClassName, $methodName);
        }
    }

    private function registerService(string $serviceName, string $className, string $methodName) {
        if (!$this->compiler->has($serviceName)) {
            $factoryDefinition = new FactoryCallDefinition($serviceName, $className, $methodName, [new ContainerDefinition()]);

            $this->compiler->addDumpableDefinition($factoryDefinition);
        } else {
            // The new service will be created under the name 'xxx_decorated_y'
            // The old service will be moved to the name 'xxx_decorated_y.inner'
            // This old service will be accessible through a callback represented by 'xxx_decorated_y.callbackwrapper'
            // The $servicename becomes an alias pointing to 'xxx_decorated_y'

            $previousDefinition = $this->compiler->getDumpableDefinition($serviceName);
            while ($previousDefinition instanceof Reference) {
                $previousDefinition = $this->compiler->getDumpableDefinition($previousDefinition->getAlias());
            }

            $oldServiceName = $serviceName;
            $serviceName = $this->getDecoratedServiceName($serviceName);
            $innerName = $serviceName.'.inner';
            $callbackWrapperName = $serviceName.'.callbackwrapper';

            $innerDefinition = new FactoryCallDefinition($innerName, $previousDefinition->getFactory(), $previousDefinition->getMethodName(), $previousDefinition->getMethodArguments());


            $callbackWrapperDefinition = new CallbackWrapperDefinition($callbackWrapperName, $innerDefinition);

            $factoryDefinition = new FactoryCallDefinition($serviceName, $className, $methodName, [new ContainerDefinition(), $callbackWrapperDefinition]);


            $this->compiler->addDumpableDefinition($factoryDefinition);
            $this->compiler->addDumpableDefinition($innerDefinition);
            $this->compiler->addDumpableDefinition($callbackWrapperDefinition);
            $this->compiler->addDumpableDefinition(new AliasDefinition($oldServiceName, $serviceName));
        }

    }

    private function getDecoratedServiceName(string $serviceName) : string {
        $counter = 1;
        while ($this->compiler->has($serviceName.'_decorated_'.$counter)) {
            $counter++;
        }
        return $serviceName.'_decorated_'.$counter;
    }
}