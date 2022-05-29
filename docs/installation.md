# Mollie Module Installation

1. Add to your application via composer:
    ```bash
    composer require luttje/vanilo-mollie 
    ```
2. Add the module to `config/concord.php`:
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
