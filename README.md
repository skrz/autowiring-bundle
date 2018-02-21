# Skrz\Bundle\AutowiringBundle

[![Build Status](https://img.shields.io/travis/skrz/autowiring-bundle.svg?style=flat-square)](https://travis-ci.org/skrz/autowiring-bundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/skrz/autowiring-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/skrz/autowiring-bundle)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/skrz/autowiring-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/skrz/autowiring-bundle)
[![Downloads this Month](https://img.shields.io/packagist/dm/skrz/autowiring-bundle.svg?style=flat-square)](https://packagist.org/packages/skrz/autowiring-bundle)
[![Latest stable](https://img.shields.io/packagist/v/skrz/autowiring-bundle.svg?style=flat-square)](https://packagist.org/packages/skrz/autowiring-bundle)

> Annotation-based autowiring for Symfony 4 dependency injection container

## Installation

Add as [Composer](https://getcomposer.org/) dependency:

```sh
$ composer require skrz/autowiring-bundle
```

Then add `SkrzAutowiringBundle` to Symfony Kernel:

```php
use Skrz\Bundle\AutowiringBundle\SkrzAutowiringBundle;

class AppKernel
{

    public function registerBundles()
    {
        return [
            ...
            new SkrzAutowiringBundle()
            ...
        ];
    }

}
```

## Usage

Annotate your application components using `@Component` annotation and its subclasses, or so called "stereotypes".
Predefined stereotypes are `@Controller`, `@Repository`, and `@Service`, e.g.:

```php
use Skrz\Bundle\AutowiringBundle\Annotation\Controller;

/**
 * @Controller
 */
class HomepageController
{
    ...
}
```

Create your own application stereotypes by subclassing `@Component`.

### Constructor dependency injection

```yaml
// services.yml
services:
  Example\HomepageController: ~
```

```php
namespace Example;

use Skrz\Bundle\AutowiringBundle\Annotation\Controller;

/**
 * @Controller
 */
class HomepageController
{

    /**
     * @var SomeService
     */
    private $someService;

    public function __construct(SomeService $someService)
    {
        $this->someService = $someService;
    }

    ...

}
```

`SomeService` is automatically injected into `HomepageController` instance during creation in container.

Note that constructor is **ALWAYS** autowired if there is not enough `arguments` specified in `services.yml`. If you really
do not want the constructor to be autowired, add the service to `ignored_services` configuration directive.

Note: if you need to specify some of the constructor arguments and autowire other constructor aurguments, you need
to configure your service the following way:

```yaml
// services.yml
services:
  Example\HomepageController:
    arguments: 
      someParameter: %kernel.whatever%
```

```php
namespace Example;

use Skrz\Bundle\AutowiringBundle\Annotation\Controller;

/**
 * @Controller
 */
class HomepageController
{

    /**
     * @var SomeService
     */
    private $someService;
    
    /**
     * @var string
     */
    private $someParameter;

    public function __construct(SomeService $someService, $someParameter)
    {
        $this->someService = $someService;
        $this->someParameter = $someParameter;
    }

    ...

}
```

The `$someService` argument is autowired and the `$someParameter` argument is injected depending on the configuration.

### Method dependency injection

```yaml
// services.yml

services:
  Example\HomepageController: ~
```

```php
namespace Example;

use Skrz\Bundle\AutowiringBundle\Annotation\Controller;
use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;

/**
 * @Controller
 */
class HomepageController
{

    /**
     * @var SomeService
     */
    private $someService;

    /**
     * @param SomeService $someService
     * @return void
     *
     * @Autowired
     */
    public function setSomeService(SomeService $someService)
    {
        $this->someService = $someService;
    }

    ...

}
```

### Property dependency injection

```yaml
// services.yml

services:
  Example\HomepageController: ~
```

```php
namespace Example;

use Skrz\Bundle\AutowiringBundle\Annotation\Controller;
use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;

/**
 * @Controller
 */
class HomepageController
{

    /**
     * @var SomeService
     *
     * @Autowired
     */
    public $someService;

    ...

}
```

Note: using property dependency injection is quite addictive.

### Property parameter injection

You can also inject container parameters using `@Value` annotation.

```yaml
// services.yml

services:
  Example\HomepageController: ~
```

```php
namespace Example;

use Skrz\Bundle\AutowiringBundle\Annotation\Controller;
use Skrz\Bundle\AutowiringBundle\Annotation\Value;

/**
 * @Controller
 */
class HomepageController
{

    /**
     * @var string
     *
     * @Value("%kernel.root_dir%")
     */
    public $rootDir;

    ...

}
```

Pro-Tip: inject always scalar values, do not inject arrays. When you inject scalar values, their presence in container
is validated during container compilation.

### Autoscan

Autoscan was a feature of version 1.x of `SkrzAutowiringBundle`. However, since Symfony 4.0, container supports
[this feature](https://symfony.com/doc/current/service_container.html#importing-many-services-at-once-with-resource)
natively. Therefore, it was removed from the bundle and you should use `resource` key to import directories of services.

```yml
// services.yml

services:
  Example\:
    resource: "../path/to/controllers/*Controller.php"
```

```php
namespace Example;

use Skrz\Bundle\AutowiringBundle\Annotation\Controller;
use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;

/**
 * @Controller
 */
class HomepageController
{

    /**
     * @var SomeService
     *
     * @Autowired
     */
    public $someService;

    ...

}
```
        
## Configuration

```yml
# container extension key is "autowiring"
autowiring:

  # these service IDs won't be processed by autowiring
  ignored_services:
    # either specify exact service IDs
    - kernel
    - http_kernel

    # or use regular expressions (they must start with "/")
    - /^debug\./
    - /^file/

  # match interfaces to exact services
  preferred_services:
    Psr\Log\LoggerInterface: logger
    Monolog\Logger: logger
    Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface: session.storage.native

  # if you create your own stereotypes, you must add then here
  fast_annotation_checks: [ @Task, @Widget ]
```

## License

The MIT license. See [LICENSE](LICENSE) file.
