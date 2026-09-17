# SmartResponse features and scope

**Content type:** Reference  
**Goal:** Explain what SmartResponse owns, what it integrates, and where your application or transport runtime remains responsible.

SmartResponse gives Laravel applications one response pipeline for web clients, APIs, legacy clients, and protocol adapters. This page lists the current capabilities so you can choose the package with a clear scope.

## Core response pipeline

The manager accepts one payload and selects a response builder. You can choose the format in code, through a named profile, or through request metadata.

- `SmartResponsePayload` carries data, message, errors, status, headers, metadata, pagination, caching, and view options
- `SmartResponseManager` applies detection, profiles, builders, events, logging, and optional caching
- `ResponseFormatterRegistry` accepts application-defined formatters
- `SmartResponseBuilder` supports fluent response construction without changing existing helpers
- `SmartResponsePreparing` and `SmartResponsePrepared` expose lifecycle events

## Request detection

SmartResponse uses this precedence when it selects an API format:

1. An explicit format passed by the controller
2. The `X-Smart-Response-Format` header
3. The configured query parameter, `format` by default
4. A supported route suffix such as `.json`, `.xml`, `.legacy`, `.graphql`, or `.soap`
5. The `Accept` header
6. The configured default format, `json` by default

The detector also recognizes Laravel JSON expectations, configured API route prefixes and patterns, and Bearer-token requests. Unsupported explicit values fall back to the configured default so existing applications keep their current behavior.

## Response formats

| Format | Included behavior | Boundary |
| --- | --- | --- |
| `json` | Standard `success`, `message`, `data`, `meta`, and `errors` envelope | Laravel HTTP response |
| `legacy` | Configurable `status`, `message`, `data`, and `errors` keys | Your existing client contract |
| `xml` | XML version of the standard API envelope | Laravel HTTP response |
| `graphql` | GraphQL-compatible `data` and `errors` envelope | Schema and query execution remain application-owned |
| `soap` | SOAP 1.1 XML envelope formatter | Native SOAP server adapter or another SOAP host |
| web | Blade, redirect, flash, and toast response paths | Laravel web request |
| Inertia | Inertia page rendering through the optional adapter | `inertiajs/inertia-laravel` |
| Livewire | Payload flag for the application Livewire path | Livewire component behavior remains application-owned |

## API behavior

The API layer includes validation and exception formatting, custom status codes, custom headers, API resources and collections, length-aware pagination, simple pagination, cursor pagination, request metadata, optional API version metadata, and rate-limit responses with `Retry-After`.

The API layer can cache successful configured-status GET responses when you enable caching and provide a cache key or use the generated request key. Cache entries are scalar response snapshots, vary by URL, negotiated format, and configured request headers, and authenticated responses require both an explicit opt-in and an isolated cache key. Dynamic timestamp or request-ID metadata disables caching by default to prevent stale correlation data. It can log response preparation when logging is enabled.

## Web behavior

The web builder supports Blade views, redirects, session flash values, toast values, and configurable default redirect routes. The Inertia adapter checks for the optional Inertia runtime before it renders a page. The package keeps web and API selection in the same controller method.

## Protocol runtimes

The package includes these transport integrations:

- **SOAP**: `SoapServerAdapter` wraps PHP's native [`SoapServer`](https://www.php.net/class.soapserver.php). PHP's `soap` extension must be enabled. WSDL and non-WSDL service objects are supported through the adapter options.
- **WebSocket**: `SmartResponseWebSocketComponent` handles JSON messages through [Ratchet](https://github.com/ratchetphp/Ratchet). `WebSocketServer` starts a Ratchet application and sends normalized `WebSocketMessage` envelopes.
- **gRPC**: `GrpcHandler` accepts a request and metadata, then returns a normalized `GrpcResponse` array. Your HTTP/2/protobuf host owns framing, generated service bindings, serialization, streaming, and health checks. The [official PHP gRPC quickstart](https://grpc.io/docs/languages/php/quickstart/) documents the client library boundary.
- **Webhook**: `WebhookPayload` creates JSON-safe event bodies and verifies HMAC signatures. Your application owns delivery, retries, queueing, idempotency, and delivery logs.

The package does not bundle every server runtime. SOAP is part of PHP, while WebSocket and gRPC require a compatible host. Optional runtimes stay outside the core dependency graph so Laravel applications that only return HTTP responses do not inherit transport conflicts.

## Extension and operations features

- **Profiles**: define named contracts such as `modern-api` and `legacy-v1`
- **Custom formatters**: register a format without changing the manager
- **Localization**: translate standard messages with a configurable fallback locale
- **Middleware**: enable the `smart.response` middleware alias
- **Production safeguards**: opt-in cache-backed fixed-window rate limits, declared request-payload limits, and additive security headers
- **OpenAPI examples**: generate reusable example payloads for documentation
- **Doctor command**: inspect PHP extensions, configured formats, and protocol readiness
- **Configuration publishing**: publish the response configuration and language files
- **Auto-discovery**: Laravel discovers the service provider and facade alias

## What remains application-owned

SmartResponse does not own business rules, authentication, authorization, validation rules, database queries, route definitions, GraphQL schemas, protobuf files, generated gRPC services, WebSocket authorization policy, webhook delivery workers, or protocol infrastructure.

Rate limiting is deliberately middleware-scoped, so an application decides which routes are protected and which shared cache store provides atomic counters. Reverse-proxy limits, trusted-proxy configuration, WAF rules, authentication, authorization, CSP, and full request-body streaming limits remain application or infrastructure responsibilities.

This boundary lets you keep one controller and one response contract while choosing the transport runtime that fits your deployment. It also prevents the package from claiming a server capability that the underlying PHP ecosystem does not provide.

## Planned capabilities

The platform plan tracks the next expansion areas: RFC 7807 and JSON:API profiles, field selection and links, ETags, versioned client profiles, GraphQL execution, gRPC streaming and health checks, WebSocket authentication and acknowledgements, webhook retries and idempotency, observability hooks, security hardening, and compatibility CI.

See the [platform plan](SMART_RESPONSE_PLATFORM_PLAN.md) for the implementation status of each area.
