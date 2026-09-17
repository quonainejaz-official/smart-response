# SmartResponse

[![Latest Version on Packagist](https://img.shields.io/packagist/v/quonain/smart-response.svg)](https://packagist.org/packages/quonain/smart-response)
[![License](https://img.shields.io/packagist/l/quonain/smart-response.svg)](https://packagist.org/packages/quonain/smart-response)
[![PHP Version](https://img.shields.io/packagist/php-v/quonain/smart-response.svg)](https://packagist.org/packages/quonain/smart-response)

**SmartResponse** is a PHP response library for standardizing and transforming application and REST API responses. It provides a zero-dependency core for plain PHP and framework adapters for Laravel, with JSON, XML, legacy API, and web response support where your application needs it.

Install it with Composer:

```bash
composer require quonain/smart-response
```

The smallest framework-agnostic response looks like this:

```php
$response = (new \Quonain\SmartResponse\Core\SmartResponse())
    ->success(['id' => 1], 'User loaded');

echo $response->content();
```

Use the Laravel integration when you want one controller API for REST JSON, legacy JSON, XML, Blade, Inertia, and other supported adapters.

Current release line: `2.0.0` (see `Quonain\\SmartResponse\\Core\\Version::CURRENT`).

Releases are automated from GitHub Actions: run the **Release** workflow, enter a semantic version and release note, and it updates the changelog/version constant, runs tests and PHPStan, commits, and creates the matching `vX.Y.Z` tag. Locally, the same preparation is available with `composer release -- 2.0.0 "Release note"`.

## Any PHP framework or plain PHP

The core has no framework dependency. It returns an immutable response value that your framework can emit with its own response object:

```php
use Quonain\SmartResponse\Core\ArrayCacheStore;
use Quonain\SmartResponse\Core\FixedWindowRateLimiter;
use Quonain\SmartResponse\Core\SmartResponse;

$responses = new SmartResponse();
$limiter = new FixedWindowRateLimiter(new ArrayCacheStore());
$limit = $limiter->attempt('user-or-ip:'.$identity, limit: 60, decaySeconds: 60);

$response = $limit['allowed']
    ? $responses->success(['id' => 1], 'User loaded')
    : $responses->rateLimited(retryAfter: $limit['retry_after']);

http_response_code($response->status());
foreach ($response->headers() as $name => $value) {
    header("{$name}: {$value}");
}
echo $response->content();
```

For CodeIgniter 4, pass `status()`, `headers()`, and `body()` to its response service:

```php
$result = (new \Quonain\SmartResponse\Core\SmartResponse())
    ->success(['id' => 1], 'User loaded');

$response = $this->response->setStatusCode($result->status());
foreach ($result->headers() as $name => $value) {
    $response->setHeader($name, $value);
}

return $response->setJSON($result->body());
```

The core also supports legacy JSON (`legacy()`), GraphQL JSON (`graphQl()`), XML (`xml()`), and standardized rate-limit output. Framework-owned concerns—views, redirects, sessions, queues, and server runtimes—remain adapter responsibilities. For production rate limits, implement `Core\\CacheStore` with your framework's shared atomic cache (for example Redis); `ArrayCacheStore` is process-local only.

## Read this before using SmartResponse

SmartResponse solves response selection and response shape. It does not replace Laravel controllers, routing, authentication, validation, queues, database code, GraphQL schema execution, protobuf generation, or a protocol server runtime. Your controller still owns the business operation. SmartResponse owns the output contract around that operation.

The intended migration looks like this:

1. Keep the existing controller method and business logic.
2. Replace repeated response construction with `smartResponse()` or the fluent builder.
3. Let SmartResponse detect the request, or select a format or named profile explicitly.
4. Keep the same payload while SmartResponse returns a web response, an API response, or a protocol-specific envelope.

For example, the same controller can serve a Blade request and an API request:

```php
public function show(User $user)
{
    return smartResponse($user)
        ->message('User loaded')
        ->view('users.show');
}
```

The default API contract is:

```json
{
  "success": true,
  "message": "User loaded",
  "data": {"id": 123},
  "meta": {"request_id": "…"},
  "errors": null
}
```

You can preserve an existing legacy contract with a profile or an explicit `legacy` format. You can add a custom formatter when your application needs another envelope. You do not need a second controller for each response shape.

## Production safeguards

The `smart.response` middleware is the package's opt-in protection boundary. It can apply a cache-backed fixed-window rate limit, reject oversized declared request bodies, and add conservative response headers without overwriting headers set by your application. Enable only the safeguards your application needs:

```php
// config/smart-response.php
'cache' => [
    'enabled' => true,
    'ttl' => 60,
    'store' => 'redis',
    // Authenticated responses remain uncached unless explicitly opted in.
],
'meta' => [
    'include_timestamp' => false,
    'include_request_id' => false,
],
'rate_limit' => [
    'enabled' => true,
    'max_attempts' => 120,
    'decay_seconds' => 60,
    'store' => 'redis',
    'key' => 'user_or_ip',
],
'payload_limits' => ['enabled' => true, 'max_bytes' => 1_048_576],
'security_headers' => ['enabled' => true],
```

Apply the middleware to the routes you want to protect:

```php
Route::middleware('smart.response')->group(function () {
    Route::get('/api/users', UsersController::class);
});
```

Cached responses are scalar snapshots, not framework response objects. Only successful configured-status `GET` API responses are cached; keys vary by URL, negotiated format, and `Accept` by default. Dynamic timestamp and request-ID metadata disables caching by default, so turn those values off for cacheable endpoints. Authenticated caching requires both an explicit opt-in and a per-user cache key. For distributed rate limiting, use an atomic shared store such as Redis rather than the array or file drivers.

### What the current release covers

| Surface | Current capability | Runtime requirement |
| --- | --- | --- |
| Laravel web | Blade views, redirects, flash messages, toast data, Inertia adapter | Laravel |
| REST API | JSON envelope, status helpers, validation and exception output | Laravel |
| Legacy API | Configurable legacy keys and named profiles | Laravel |
| XML | Standardized XML envelope | Laravel |
| GraphQL output | `{ data, errors }` response formatter | GraphQL execution server remains application-owned |
| SOAP | SOAP formatter and native `SoapServer` adapter | PHP `soap` extension |
| WebSocket | JSON message component and Ratchet server runner | Compatible Ratchet runtime |
| gRPC | Metadata-aware normalized application handler | HTTP/2 and protobuf host such as RoadRunner or FrankenPHP |
| Webhook | JSON-safe payload and HMAC signature verification | Delivery, retry, queue, and idempotency remain application-owned |

The gRPC boundary is deliberate. The official PHP gRPC package supplies a client library, so SmartResponse does not claim to provide a native PHP gRPC server. It gives your selected HTTP/2 host the same normalized application response that the other adapters use.

See the complete capability inventory and implementation boundaries in [features and scope](docs/FEATURES_AND_SCOPE.md). See transport setup in [runtime servers](docs/runtime-servers.md).

---

## Table of contents

- [Features](#features)
- [Documentation](#documentation)
- [Read this before using SmartResponse](#read-this-before-using-smartresponse)
- [Current release scope](#what-the-current-release-covers)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [How API vs Web is detected](#how-api-vs-web-is-detected)
- [Usage](#usage)
  - [Unified response](#unified-smartresponse)
  - [HTTP shortcuts](#http-shortcuts)
  - [Facade & helpers](#facade--global-helpers)
  - [Response macros](#response-macros)
  - [Pagination](#pagination)
  - [API meta enrichment](#api-meta-enrichment)
  - [Rate limiting](#rate-limiting)
  - [Caching](#caching-api)
- [Exception handling](#exception-handling-api)
- [Configuration](#configuration)
- [Middleware & events](#middleware)
- [Testing](#testing)
- [Changelog & license](#changelog)

---

## Features

SmartResponse includes these features in the current codebase:

- **Request detection**: `Accept` headers, JSON expectations, `/api/*` routes, Bearer tokens, explicit format headers, query parameters, route suffixes, and named profiles
- **API formats**: JSON, XML, legacy JSON, GraphQL output, and SOAP XML
- **Web responses**: Blade views, redirects, session flash messages, toast data, Inertia rendering, and Livewire flags
- **One response API**: `HasSmartResponse`, `smartResponse()`, the `SmartResponse` facade, global helpers, response macros, and fluent builders
- **HTTP shortcuts**: created, no content, not found, unauthorized, forbidden, validation error, and rate-limit responses
- **Payload features**: pagination metadata, API resources and collections, custom headers, status codes, metadata, and request IDs
- **Compatibility**: configurable legacy keys, named profiles, explicit format overrides, and backward-compatible JSON defaults
- **Operations**: optional API caching, structured logging hooks, lifecycle events, localization, and the `smart-response:doctor` command
- **Extension points**: response formatter registry, custom profiles, OpenAPI example payloads, and protocol-neutral transport value objects
- **Runtime integrations**: native PHP SOAP server adapter, Ratchet WebSocket server component, and gRPC handler for an external HTTP/2/protobuf host
- **Framework support**: Laravel 10, 11, 12, and 13 with PHP 8.2 or newer

---

## Requirements

- PHP `^8.2`
- Laravel `^10.0` · `^11.0` · `^12.0` · `^13.0`

The core response value object does not require Laravel. Laravel-only features such as Blade views, middleware, facades, macros, and service-provider integration require the corresponding Laravel application components.

---

## Installation

```bash
composer require quonain/smart-response
```

Or pin the latest 1.x release:

```bash
composer require quonain/smart-response:^1.1
```

Laravel **auto-discovers** the service provider — no manual registration.

### Publish assets

```bash
# Configuration
php artisan vendor:publish --tag=smart-response-config

# Translations
php artisan vendor:publish --tag=smart-response-lang
```

> **Packagist:** After a new release, open your [package page](https://packagist.org/packages/quonain/smart-response) and click **Update** if Composer does not see the latest tag yet. Enable the GitHub hook under package settings for automatic sync.

---

## Quick start

### 1. Add the trait

```php
use Illuminate\Http\Request;
use Quonain\SmartResponse\Traits\HasSmartResponse;

class UserController extends Controller
{
    use HasSmartResponse;

    public function index(Request $request)
    {
        $users = User::paginate(15);

        return $this->smartResponse(
            request: $request,
            data: UserResource::collection($users),
            view: 'users.index',
            message: 'users.fetched', // translation key (optional)
        );
    }

    public function store(Request $request)
    {
        $user = User::create($request->validated());

        return $this->smartCreated($user, 'users.created');
    }
}
```

| Request type | Result |
|--------------|--------|
| API (`Accept: application/json`, `/api/*`, Bearer token, …) | Standard JSON |
| Web (`text/html`, normal browser) | Blade view `users.index` with `$data`, `$message`, … |

### 2. Default JSON shape

```json
{
  "success": true,
  "message": "Users fetched successfully",
  "data": [],
  "meta": {
    "timestamp": "2026-05-21T12:00:00+00:00",
    "request_id": "550e8400-e29b-41d4-a716-446655440000",
    "current_page": 1,
    "per_page": 15,
    "total": 100
  },
  "errors": null
}
```

### Legacy API responses

Existing clients can opt into a legacy envelope per response by setting
`format: 'legacy'`. The modern envelope remains the default.

```php
return $this->smartResponse(
    request: $request,
    data: $user,
    message: 'User loaded',
    format: 'legacy',
);
```

The default legacy shape is `status`, `message`, `data`, and `errors`. To match
an existing contract, publish the config and change `smart-response.legacy.keys`.

### Protocol engines

The same payload can be selected for additional API protocols:

```php
return $this->smartResponse(request: $request, data: $user, format: 'graphql');
return $this->smartResponse(request: $request, data: $user, format: 'soap');
```

`graphql` returns the standard GraphQL `{ data, errors }` envelope.
`soap` returns a SOAP 1.1 XML envelope. Both are opt-in and preserve the
existing JSON default.

For transports that are not ordinary HTTP responses, use the built-in
transport-neutral value objects and pass them to your chosen runtime:

```php
$grpc = new \Quonain\SmartResponse\Support\GrpcResponse(data: $user, message: 'Loaded');
$socket = new \Quonain\SmartResponse\Support\WebSocketMessage(data: $user, event: 'user.loaded');
$webhook = \Quonain\SmartResponse\Support\WebhookPayload::create(
    event: 'user.created', data: $user, secret: config('services.partner.secret'),
);
```

`GrpcResponse::toArray()` is normalized by `Runtime\\Grpc\\GrpcHandler` for a
real HTTP/2/protobuf host, while `WebSocketMessage::encode()` can be sent
through the included Ratchet component or another WebSocket server.
`WebhookPayload` creates a JSON-safe event body and an
optional HMAC-SHA256 signature. Receivers can validate it with
`WebhookPayload::verify($payload, $secret, $payload['signature'])`. The library does not force a specific gRPC,
WebSocket, or HTTP client dependency on applications.

### Runtime servers

SOAP can be served through `Runtime\\Soap\\SoapServerAdapter` when the PHP
SOAP extension is enabled. WebSocket applications can use
`Runtime\\WebSocket\\SmartResponseWebSocketComponent` with a Ratchet runtime.
For gRPC, PHP's official package provides clients rather than a native server;
`Runtime\\Grpc\\GrpcHandler` is the application handler to mount in a real
HTTP/2 host such as RoadRunner or FrankenPHP.

### Fluent response builder and profiles

For code shared by multiple clients, use the fluent builder and select a named
response contract without changing the controller logic:

```php
return SmartResponse::make($user)
    ->request($request)
    ->profile('legacy-v1')
    ->message('User loaded')
    ->meta(['resource' => 'user'])
    ->send();
```

The built-in `modern-api` and `legacy-v1` profiles can be customized in
`config/smart-response.php`. A response can also select a format through the
`X-Smart-Response-Format` header, `?format=...`, or a route suffix such as
`/users.xml`. Explicit payload options always take priority.

---

## How API vs Web is detected

SmartResponse treats a request as **API** when any of these match (configurable in `config/smart-response.php`):

1. `Accept` contains `application/json` or `application/vnd.api+json`
2. `Accept` contains `application/xml` or `text/xml`
3. Route matches `api/*` or configured prefixes (`api` by default)
4. Laravel `expectsJson()` is true (AJAX, etc.)
5. **`Authorization: Bearer …` is present** (`detection.bearer_as_api` — ideal for Sanctum / Passport SPA or mobile apps)

Otherwise the request is handled as **Web** (view or redirect).

---

## Usage

### Unified `smartResponse()`

```php
return $this->smartResponse(
    request: $request,
    data: $data,
    view: 'users.index',
    viewData: ['title' => 'Users'],
    message: 'Success',
    success: true,
    errors: null,
    meta: ['custom' => 'value'],
    status: 200,
    route: 'users.index',       // web redirect
    routeParameters: [],
    format: null,               // auto: json | xml
    flash: true,
    toast: false,
    cacheKey: null,
    cacheTtl: null,
    headers: ['X-Custom' => '1'],
    inertiaComponent: 'Users/Index',
    useInertia: false,
    useLivewire: false,
);
```

### HTTP shortcuts

| Trait method | Facade / Manager | Status | Use case |
|--------------|------------------|--------|----------|
| `smartSuccess()` | `SmartResponse::success()` | 200 | OK with data |
| `smartCreated()` | `SmartResponse::created()` | 201 | Resource created |
| `smartNoContent()` | `SmartResponse::noContent()` | 204 | Delete / empty OK |
| `smartError()` | `SmartResponse::error()` | 4xx/5xx | Generic error |
| `smartNotFound()` | `SmartResponse::notFound()` | 404 | Missing resource |
| `smartUnauthorized()` | `SmartResponse::unauthorized()` | 401 | Not logged in |
| `smartForbidden()` | `SmartResponse::forbidden()` | 403 | No permission |
| `smartValidationError()` | `SmartResponse::validationError()` | 422 | Form / API validation |

```php
return $this->smartSuccess($users, 'Users loaded');
return $this->smartCreated($user, 'users.created');
return $this->smartNoContent();
return $this->smartNotFound('error.not_found');
return $this->smartUnauthorized();
return $this->smartForbidden('error.forbidden');
return $this->smartValidationError($validator->errors());
```

### Facade & global helpers

```php
use Quonain\SmartResponse\Facades\SmartResponse;

SmartResponse::success($data, 'Done');
SmartResponse::created($data, 'Created');
SmartResponse::notFound('Not found');

// Global helpers (no trait required)
smart_response(request: $request, data: $users, view: 'users.index');
smart_created($user, 'users.created');
smart_not_found('error.not_found');
smart_rate_limit_response(retryAfter: 60);
```

### Response macros

```php
response()->smart($data, 'OK');
response()->smartSuccess($data, 'Saved');
response()->smartError('Failed', ['code' => 'X'], 400);
response()->smartCreated($data, 'Created');
response()->smartNotFound('Not found');
```

### Pagination

**Length-aware** — pass a paginator; meta keys are merged automatically:

```php
return $this->smartResponse(
    request: $request,
    data: User::paginate(20),
    view: 'users.index',
);
```

**Cursor** — works with `cursorPaginate()`:

```php
return $this->smartResponse(
    request: $request,
    data: User::orderBy('id')->cursorPaginate(15),
);
```

Meta includes: `per_page`, `path`, `next_cursor`, `prev_cursor`, `has_more`.

### API Resources & XML

```php
// API Resource collection
return $this->smartResponse(
    request: $request,
    data: UserResource::collection($users),
    view: 'users.index',
);

// Force or auto-detect XML
return $this->smartResponse(request: $request, data: $users, format: 'xml');
```

### API meta enrichment

Enabled by default (`meta.enabled` in config). Every API response can include:

| Meta key | Source |
|----------|--------|
| `timestamp` | Current time (ISO 8601) |
| `request_id` | `X-Request-Id` header or auto UUID |
| `api_version` | `X-API-Version` header or `meta.api_version` config |

```php
// config/smart-response.php
'meta' => [
    'enabled' => true,
    'include_timestamp' => true,
    'include_request_id' => true,
    'request_id_header' => 'X-Request-Id',
    'include_api_version' => true,
    'api_version' => '1.0',
],
```

### Rate limiting

Returns a standard error JSON with **`Retry-After`** header:

```php
// Uses config defaults (429 + retry_after_seconds)
return smart_rate_limit_response();

// Custom message and seconds
return smart_rate_limit_response('Slow down', 120);
```

### Caching (API)

Enable in config, then cache GET API responses:

```php
return $this->smartResponse(
    request: $request,
    data: $expensiveData,
    cacheKey: 'users.index',
    cacheTtl: 120,
);
```

Without `cacheKey`, a hash of the full URL + `Accept` header is used.

### Web redirect with flash & toast

```php
return $this->smartResponse(
    request: $request,
    message: 'User created',
    route: 'users.index',
    toast: true,
    status: 201,
);
```

---

## Exception handling (API)

Register in `bootstrap/app.php` (Laravel 11+):

```php
use Quonain\SmartResponse\Exceptions\Handler\SmartResponseExceptionHandler;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (Throwable $e, $request) {
        return app(SmartResponseExceptionHandler::class)->render($request, $e);
    });
})
```

API requests receive the same JSON envelope; web requests fall through to Laravel’s default handling.

---

## Configuration

Publish `config/smart-response.php` and adjust:

| Key | Description |
|-----|-------------|
| `api.*` | JSON keys: `success`, `message`, `data`, `meta`, `errors` |
| `detection.*` | JSON/XML accepts, route prefixes, **`bearer_as_api`** |
| `meta.*` | Timestamp, request ID, API version injection |
| `default_format` | `json` or `xml` |
| `status_codes.*` | Defaults for 200, 201, 204, 401, 403, 404, 422, 429, 500 |
| `web.*` | Flash / toast session keys, default redirect route |
| `inertia.enabled` | Inertia.js adapter |
| `livewire.enabled` | Livewire hooks |
| `locale.enabled` | Translate message keys via lang files |
| `cache.enabled` | Cache GET API responses |
| `logging.enabled` | Log each response |
| `events.enabled` | `SmartResponsePreparing` / `SmartResponsePrepared` |
| `rate_limit.*` | 429 message and `retry_after_seconds` |
| `graphql.enabled` | GraphQL response `Accept` detection |

### Multi-language messages

```php
return $this->smartResponse(message: 'users.fetched');
// → lang/vendor/smart-response/en/messages.php
```

Built-in keys include: `users.fetched`, `users.created`, `error.not_found`, `error.unauthorized`, `error.forbidden`, `error.rate_limit`, and more.

### Inertia.js

```php
// config/smart-response.php
'inertia' => ['enabled' => true],

return $this->smartResponse(
    request: $request,
    data: $users,
    inertiaComponent: 'Users/Index',
    useInertia: true,
);
```

---

## Middleware

Alias: `smart.response` (enabled by default)

```php
Route::middleware('smart.response')->group(function () {
    // ...
});
```

### Events

```php
use Quonain\SmartResponse\Events\SmartResponsePreparing;
use Quonain\SmartResponse\Events\SmartResponsePrepared;

Event::listen(SmartResponsePreparing::class, fn ($e) => /* mutate payload */);
Event::listen(SmartResponsePrepared::class, fn ($e) => /* inspect response */);
```

### OpenAPI / Swagger

```php
use Quonain\SmartResponse\Support\OpenApiExample;

OpenApiExample::successExample();
OpenApiExample::errorExample();
```

---

## Testing

```bash
composer install
composer test
composer analyse
composer validate --strict
composer audit
```

Check the local runtime before enabling optional protocol integrations:

```bash
php artisan smart-response:doctor
```

---

## Package structure

```text
smart-response/
├── config/smart-response.php
├── lang/en/messages.php
├── src/
│   ├── Contracts/
│   ├── Detectors/          # API vs Web detection
│   ├── DTO/
│   ├── Formatters/         # JSON, XML
│   ├── Builders/
│   ├── Services/
│   ├── Traits/             # HasSmartResponse
│   ├── Facades/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/Middleware/
│   ├── Macros/
│   └── Support/            # MetaEnricher, Pagination, i18n, …
├── tests/
├── examples/
└── README.md
```

---

## Documentation

- [Getting started](docs/getting-started.md)
- [Installation](docs/installation.md)
- [Basic usage](docs/basic-usage.md)
- [Response formats](docs/responses.md)
- [JSON responses](docs/json.md)
- [XML responses](docs/xml.md)
- [Legacy API compatibility](docs/legacy.md)
- [Request detection and format selection](docs/content-negotiation.md)
- [Customization and extension](docs/customization.md)
- [Framework integration](docs/framework-integration.md)
- [Architecture](docs/architecture.md)
- [Migration guide](docs/migration.md)
- [FAQ](docs/faq.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Search intent coverage](docs/search-intent.md)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and the current `2.0.0` release notes.

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

---

## License

MIT © [Quonain Ejaz](https://github.com/quonainejaz-official). See [LICENSE](LICENSE).

## Outbound API clients

Configure providers in `config/smart-response.php` and use the same facade for outbound calls:

```php
'http' => ['providers' => [
    'github' => [
        'base_url' => env('GITHUB_API_URL', 'https://api.github.com'),
        'auth' => ['type' => 'bearer', 'token' => env('GITHUB_TOKEN')],
    ],
]],
```

```php
$response = SmartResponse::request('github')
    ->get('/users')
    ->query(['page' => 1])
    ->headers(['Accept' => 'application/vnd.github+json'])
    ->retry(3)
    ->send();

$users = $response->decoded();
```

The client supports GET, POST, PUT, PATCH, DELETE, OPTIONS and HEAD, JSON/form/multipart bodies, bearer/API-key/basic authentication, timeouts, retries with exponential backoff, response decoding, DTO mapping, host allow-lists, and response-size limits. Configure `http.max_response_bytes` and provider `allowed_hosts` for production deployments.

## Structured response telemetry

When `logging.enabled` is enabled, each response emits structured lifecycle fields including request/trace IDs, method, URL, negotiated format, status, duration, cache state and error code. Sensitive header names listed in `logging.redact` are never intended to be recorded by application hooks.
