# Skrz\Bundle\AutowiringBundle

## Installation

Add as [Composer](https://getcomposer.org/) dependency:


    {
        "require": {
            ...
            "skrz/autowiring-bundle": "dev-master"
            ...
        }
    }

Then add `AutowiringBundle` to Symfony Kernel:

    use Skrz\Bundle\AutowiringBundle\AutowiringBundle;

    class AppKernel
    {

        public function registerBundles()
        {
            return [
                ...
                new AutowiringBundle()
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

### Constructor dependency injection

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

Constructor is **ALWAYS** autowired if you need to pass parameters there are not enough `arguments` specified in `services.yml`.

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

### Property dependency injection

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

### Property parameter injection

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
         * @Value("%kernel.root_dir%)
         */
        public $rootDir;

        ...

    }

### Autoscan

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

## Configuration

    # top container extension key is "autowiring"
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

## License

The MIT license. See `LICENSE` file.
