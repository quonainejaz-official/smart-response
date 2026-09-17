# Adapters

Adapters keep protocol and framework dependencies outside the pure core.

- Register a core formatter through `ResponseFactory::register()`.
- Register a Laravel formatter through `ResponseFormatterRegistry`.
- Use the existing legacy formatter for renamed keys and old envelopes.
- Use `Runtime\Soap\SoapServerAdapter` only with `ext-soap`; it delegates the SOAP runtime instead of implementing SOAP.
- Use framework-native response conversion at the application boundary.

Business services should return DTOs/data, not Laravel responses. Controllers decide whether to use the Laravel manager, a PSR bridge, or the standalone factory.
