# Positioning

Smart Response is a small response orchestration layer for applications that need one application payload to produce modern, legacy, or custom representations.

It is not a framework, ORM, serializer replacement, SOAP engine, HTTP client, or API gateway.

Choose:

- Symfony HttpFoundation when you need a complete framework-independent HTTP response implementation.
- Symfony Serializer or JMS Serializer when serialization rules and object metadata are the primary problem.
- Laravel API Resources when the application is Laravel-only and resource transformation is sufficient.
- FOSRestBundle when a Symfony application wants its bundle-level view and format listener.
- a dedicated SOAP library or PHP SOAP extension when WSDL/protocol behavior is the primary problem.
- Smart Response when the same service data must be represented consistently across JSON, XML, legacy envelopes, and framework boundaries.
