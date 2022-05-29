# Mollie Module Installation

1. <s>Add to your project via composer:
    ```bash
    composer require luttje/vanilo-mollie 
    ```
    </s>

    *Because this package is untested and unstable it is not published to packagist.*

    To use it you'll have to clone it to your device and [require this library as a local
    package](https://mauricius.dev/require-a-local-composer-package-for-development/).
    
2. Add the module to `config/concord.php` in your project:
    ```php
    <?php
    return [
        'modules' => [
             //...
             Luttje\Mollie\Providers\ModuleServiceProvider::class,
             //...
        ],
    ]; 
    ```

---

**Next**: [Configuration &raquo;](configuration.md)
