[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thecodingmachine/yaco-service-provider/badges/quality-score.png?b=1.0)](https://scrutinizer-ci.com/g/thecodingmachine/yaco-service-provider/?branch=1.0)
[![Build Status](https://travis-ci.org/thecodingmachine/yaco-service-provider.svg?branch=1.0)](https://travis-ci.org/thecodingmachine/yaco-service-provider)
[![Coverage Status](https://coveralls.io/repos/thecodingmachine/yaco-service-provider/badge.svg?branch=1.0&service=github)](https://coveralls.io/github/thecodingmachine/yaco-service-provider?branch=1.0)

# Bridge between container-interop's service providers and YACO

This package is a bridge between [container-interop's service providers](http://github.com/container-interop/service-provider) and [YACO, the PSR-11 compliant container compiler](http://github.com/thecodingmachine/yaco).

Using this package, you can use Yaco to generate PSR-11 compliant containers that contain the services provided by container-interop's service providers.

## Usage

```php
use TheCodingMachine\Yaco\Compiler;
use TheCodingMachine\Yaco\ServiceProvider\ServiceProviderLoader;

// Create your YACO compiler.
$container = new Container();

// Create your service provider loader
$serviceProviderLoader = new ServiceProviderLoader($compiler);

// Load service providers into Yaco:
$serviceProviderLoader->load(MyServiceProvider::class);
$serviceProviderLoader->load(MyOtherServiceProvider::class);

// Services are now available in Yaco, we just need to dump the container:
$code = $compiler->compile('MyContainer');
file_put_contents(__DIR__.'/MyContainer.php', $code);
```

