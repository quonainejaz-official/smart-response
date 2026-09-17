# Competitive capability matrix

This is an interoperability and scope document, not a claim that Smart Response replaces the projects below. The comparison was reviewed against their public documentation on 2026-09-17.

| Capability | Established approach | Strength | Smart Response decision |
| --- | --- | --- | --- |
| HTTP response object | Symfony HttpFoundation | Mature status, headers, cookies, files, and streaming | Keep the core value object dependency-free; Laravel/Symfony adapters return native responses. |
| Object serialization | Symfony Serializer | Normalizers, encoders, contexts, and broad type support | Provide `SerializerInterface`; integrate Symfony Serializer or JMS through adapters. |
| Serializer handlers | JMS Serializer | Format/type-specific handlers and rich metadata | Do not reimplement; accept a serializer adapter. |
| Content negotiation | willdurand/negotiation | Focused RFC-style media-type negotiation | Ship a small core negotiator for common cases; support the library as an optional adapter for complex policies. |
| Format-agnostic controllers | FOSRestBundle | View layer, format listener, exception mapping, serializer integration | Adopt the orchestration idea without requiring Symfony or a bundle. |
| Laravel responses/resources | Laravel HTTP responses and API Resources | Native framework lifecycle, resources, views, redirects, downloads | Keep Laravel integration optional and preserve native response returns. |
| PSR HTTP messages | PSR-7/17/18 | Shared interfaces for messages, factories, and clients | Prefer bridges and optional dependencies; do not force one PSR-7 implementation. |
| SOAP | PHP SOAP extension | Actual SOAP 1.1/1.2 and WSDL runtime | Adapter only; never advertise the XML formatter as a SOAP server. |
| Error representation | RFC 7807 / framework error handlers | Standard machine-readable problem details | Add configurable problem-details mapping in the roadmap; keep current envelope stable. |

## Current boundary

Native core capabilities are response values, JSON/XML/text formatting, serialization injection, headers/status, and basic `Accept` negotiation. Laravel-specific formatters and web behavior remain optional at runtime through Composer's existing development/integration setup.

The core intentionally does not provide a database, ORM, authentication system, HTTP client, SOAP protocol engine, serializer metadata system, or framework container.

## Primary references

- [Symfony HttpFoundation](https://symfony.com/doc/current/components/http_foundation.html)
- [Symfony Serializer](https://symfony.com/doc/current/serializer.html)
- [Laravel HTTP responses](https://laravel.com/docs/12.x/responses)
- [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle)
- [JMS Serializer](https://jmsyst.com/libs/serializer)
- [willdurand/negotiation](https://github.com/willdurand/Negotiation)
- [PSR-7](https://www.php-fig.org/psr/psr-7/), [PSR-17](https://www.php-fig.org/psr/psr-17/), and [PSR-18](https://www.php-fig.org/psr/psr-18/)
- [PHP SOAP extension](https://www.php.net/soap)
