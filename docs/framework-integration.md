# Framework integration

## Laravel

Laravel receives auto-discovered service-provider, facade, helper, macro, middleware, event, translation, Blade, Inertia, and Livewire integration when the corresponding application packages are present. See the [README quick start](../README.md#quick-start).

## Plain PHP and other frameworks

Use the framework-agnostic core and map `status()`, `headers()`, and `body()` to the host framework response object. An example for CodeIgniter is in the [README](../README.md#any-php-framework-or-plain-php).

There is no dedicated Symfony adapter in this package. Symfony applications can use the core and create their own `Symfony\\Component\\HttpFoundation\\Response` adapter.
