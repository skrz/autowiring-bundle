# Skrz\Bundle\AutowiringBundle

[![Downloads this Month](https://img.shields.io/packagist/dm/skrz/autowiring-bundle.svg)](https://packagist.org/packages/skrz/autowiring-bundle)
[![Latest stable](https://img.shields.io/packagist/v/skrz/autowiring-bundle.svg)](https://packagist.org/packages/skrz/autowiring-bundle)

> Writing `services.yml` is boring, it should be automated. `Skrz\Bundle\AutowiringBundle` automates `services.yml` management.

## Installation

Add as [Composer](https://getcomposer.org/) dependency:

```sh
$ composer require skrz/autowiring-bundle:@dev
```

Then add `AutowiringBundle` to Symfony Kernel:

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


## Usage

Annotate your application components using `@Component` annotation and its subclasses - they are called "stereotypes".
Predefined stereotypes are `@Controller`, `@Repository`, and `@Service`, e.g.:

    use Skrz\Bundle\AutowiringBundle\Annotation\Controller;

    /**
     * @Controller
     */
    class HomepageController
    {
        ...
    }

Create your own application stereotypes by subclassing `@Component`.

### Constructor dependency injection

`services.yml`:

    services:
      controller.homepage:
        class: HomepageController

`HomepageController.php`:

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

`SomeService` is automatically injected into `HomepageController` instance during creation in container.

Note that constructor is **ALWAYS** autowired if there is not enough `arguments` specified in `services.yml`. If you really
do not want the constructor to be autowired, add the service to `ignored_services` configuration directive.

### Method dependency injection

`services.yml`:

    services:
      controller.homepage:
        class: HomepageController

`HomepageController.php`:

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

### Property dependency injection

`services.yml`:

    services:
      controller.homepage:
        class: HomepageController

`HomepageController.php`:

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
    
Note: using property dependency injection is quite addictive.

### Property parameter injection

You can also inject container parameters using `@Value` annotation.

`services.yml`:

    services:
      controller.homepage:
        class: HomepageController

`HomepageController.php`:

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
    
Protip: inject always scalar values, do not inject arrays. When you inject scalar values, their presence in container
is validated during container compilation.

### Autoscan

`services.yml`:

    autowiring:
      autoscan_psr4:
        "": %kernel.root_dir%/path/to/controllers


`HomepageController.php`:

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
        
## Configuration

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

      # add directories to be scanned
      autoscan_psr4:
        Skrz\Controller: %kernel.root_dir%/src/Skrz/Controller
        Skrz\Repository: %kernel.root_dir%/src/Skrz/Repository
        Skrz\Service: %kernel.root_dir%/src/Skrz/Service

## Known limitations

- Autoscan currently depends on `grep` utility is present in `PATH`. Therefore it won't work on Windows.

## License

The MIT license. See `LICENSE` file.
