<?php


namespace TheCodingMachine\Yaco\ServiceProvider;

use TheCodingMachine\Yaco\Compiler;
use TheCodingMachine\Yaco\Definition\ParameterDefinition;
use TheCodingMachine\Yaco\ServiceProvider\Fixtures\TestServiceProvider;
use TheCodingMachine\Yaco\ServiceProvider\Fixtures\TestServiceProviderOverride;

class ServiceProviderLoaderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \TheCodingMachine\Yaco\ServiceProvider\InvalidArgumentException
     */
    public function testLoadWrongClass()
    {
        $compiler = new Compiler();

        $serviceProviderLoader = new ServiceProviderLoader($compiler);
        $serviceProviderLoader->load('ThatClassDoesNotExists');
    }

    public function testLoadServiceProvider()
    {
        $compiler = new Compiler();
        $compiler->addDumpableDefinition(new ParameterDefinition('my_parameter', 'my_value'));
        $serviceProviderLoader = new ServiceProviderLoader($compiler);
        $serviceProviderLoader->load(TestServiceProvider::class);

        $code = $compiler->compile('MyContainerServiceProvider');
        file_put_contents(__DIR__.'/Fixtures/Generated/MyContainerServiceProvider.php', $code);
        require __DIR__.'/Fixtures/Generated/MyContainerServiceProvider.php';

        $myContainer = new \MyContainerServiceProvider();
        $result = $myContainer->get('serviceA');
        $this->assertInstanceOf('\\stdClass', $result);
        $this->assertEquals('my_value', $result->serviceB->parameter);
    }

    public function testLoadServiceProviderWithOverride()
    {
        $compiler = new Compiler();
        $compiler->addDumpableDefinition(new ParameterDefinition('my_parameter', 'my_value'));
        $serviceProviderLoader = new ServiceProviderLoader($compiler);
        $serviceProviderLoader->load(TestServiceProvider::class);
        $serviceProviderLoader->load(TestServiceProviderOverride::class);

        $code = $compiler->compile('MyContainerServiceProviderWithOverride');
        file_put_contents(__DIR__.'/Fixtures/Generated/MyContainerServiceProviderWithOverride.php', $code);
        require __DIR__.'/Fixtures/Generated/MyContainerServiceProviderWithOverride.php';

        $myContainer = new \MyContainerServiceProviderWithOverride();
        $result = $myContainer->get('serviceA');

        $this->assertInstanceOf('\\stdClass', $result);
        $this->assertEquals('my_value', $result->serviceB->parameter);
        $this->assertEquals('foo', $result->newProperty);
    }
}
