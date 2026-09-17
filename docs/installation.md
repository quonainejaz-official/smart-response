# Installation

## Composer

```bash
composer require quonain/smart-response
```

SmartResponse requires PHP `^8.2`. The core has no production framework dependency. Laravel features are available when Laravel supplies the Illuminate components.

Laravel auto-discovers the service provider. Publish configuration and translations when you need to customize them:

```bash
php artisan vendor:publish --tag=smart-response-config
php artisan vendor:publish --tag=smart-response-lang
```

Optional runtime integrations have their own requirements. See [framework integration](framework-integration.md) and [troubleshooting](troubleshooting.md).
